<template>
	<label
		class="label"
		:class="[formSize == 'max' ? 'max' : '']"
		:for="$t(params.label).replaceAll(' ', '_')"
		>{{ $t(params.label) }}</label
	>
	<div class="form-element-row">
		<div
			class="form-element-wrapper"
			:class="[formSize == 'max' ? 'max' : '']"
		>
			<!-- Search Input (shown when searchable is enabled) -->
			<div
				v-if="params.searchable"
				class="search-wrapper"
			>
				<input
					type="text"
					class="input-text search-input"
					v-model="searchQuery"
					:placeholder="$t('generic.search')"
					:disabled="loading"
					@focus="showDropdown = true"
					@blur="handleBlur"
					@keydown.delete="handleBackspace"
				/>
				<!-- Clear button for searchable input -->
				<button
					v-if="fieldValue && params.clearable !== false"
					type="button"
					class="clear-button"
					@mousedown.prevent="clearSelection"
					:disabled="loading"
					:title="$t('generic.clear')"
				>
					×
				</button>
				<div
					v-if="showDropdown && (filteredOptions.length > 0 || loading)"
					class="dropdown-list"
				>
					<div
						v-if="loading"
						class="dropdown-item loading-item"
					>
						Loading...
					</div>
					<div
						v-else
						v-for="option in filteredOptions"
						:key="option.value"
						class="dropdown-item"
						:class="{ selected: fieldValue === option.value }"
						@mousedown="selectOption(option)"
					>
						{{ option.label }}
					</div>
					<div
						v-if="!loading && filteredOptions.length === 0"
						class="dropdown-item no-results"
					>
						No results found
					</div>
				</div>
			</div>

			<!-- Traditional Select with Clear Option -->
			<div
				v-else
				class="select-wrapper"
			>
				<select
					class="input-text"
					@change="updateData"
					v-model="fieldValue"
					:disabled="loading"
					:class="[loading ? 'loading' : '']"
					:name="$t(params.label).replaceAll(' ', '_')"
				>
					<option value="">
						{{ loading ? $t('generic.loading') : $t('generic.select-option') }}
					</option>
					<option
						v-for="option in options"
						:key="option.value"
						:value="option.value"
					>
						{{ option.label }}
					</option>
				</select>
				<!-- Clear button for traditional select -->
				<button
					v-if="fieldValue && params.clearable !== false"
					type="button"
					class="clear-button select-clear"
					@click="clearSelection"
					:disabled="loading"
					:title="$t('generic.clear')"
				>
					×
				</button>
			</div>
		</div>
		<div
			class="form-element-error"
			:class="{ 'form-error': params.error }"
		>
			<span v-if="params.error">
				{{ Array.isArray(params.error) ? params.error[0] : params.error }}
			</span>
		</div>
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import { ref, computed, watch } from 'vue';

export default defineComponent({
	name: 'SelectField',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const { t } = useI18n();
		const route = useRoute();
		const fieldValue = ref(props.record[props.params.db_name]);
		const options = ref([]);
		const loading = ref(false);
		const error = ref(null);
		const searchQuery = ref('');
		const showDropdown = ref(false);

		// Filter options based on search query
		const filteredOptions = computed(() => {
			if (!props.params.searchable || !searchQuery.value) {
				return options.value;
			}

			const query = searchQuery.value.toLowerCase();
			return options.value.filter(
				(option) =>
					option.label.toLowerCase().includes(query) || option.value.toString().toLowerCase().includes(query)
			);
		});

		// Get translated label for an option
		const getTranslatedLabel = (originalLabel) => {
			return props.params.translate ? t(originalLabel) : originalLabel;
		};

		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		const selectOption = (option) => {
			fieldValue.value = option.value;
			searchQuery.value = option.label;
			showDropdown.value = false;
			updateData();
		};

		const clearSelection = () => {
			fieldValue.value = '';
			searchQuery.value = '';
			showDropdown.value = false;
			updateData();
		};

		const handleBackspace = (event) => {
			// Clear selection when backspace is pressed and input is empty (for searchable)
			if (props.params.searchable && !searchQuery.value && fieldValue.value) {
				clearSelection();
			}
		};

		const handleBlur = () => {
			// Delay hiding dropdown to allow click events
			setTimeout(() => {
				showDropdown.value = false;
				// Reset search query to selected option label if something is selected
				if (fieldValue.value) {
					const selectedOption = options.value.find((opt) => opt.value === fieldValue.value);
					if (selectedOption) {
						searchQuery.value = selectedOption.label;
					}
				} else {
					searchQuery.value = '';
				}
			}, 200);
		};

		const fetchOptions = async () => {
			if (!props.params.endpoint) {
				console.error('SelectField: endpoint is required in params');
				return;
			}

			loading.value = true;
			error.value = null;

			try {
				const response = await props.params.endpoint.split('.').reduce((obj, key) => obj[key], apiService)();

				if (!response.success) return;
				// Handle different response formats
				let data = response.options || response.data || response;

				// If data is an object (not array), convert it to array
				if (typeof data === 'object' && !Array.isArray(data)) {
					// Convert object to array of {value, label} pairs
					options.value = Object.entries(data).map(([key, value]) => {
						const originalLabel = typeof value === 'string' ? value : key;
						const translatedLabel = getTranslatedLabel(originalLabel, key);

						return {
							value: key,
							label: translatedLabel,
							originalLabel: originalLabel,
						};
					});
				}
				// If data is an array, map it to {value, label} format
				else if (Array.isArray(data)) {
					options.value = data
						.filter((item) => item != null) // Filter out null/undefined items
						.map((item) => {
							// If item is a string, use it as both value and label
							if (typeof item === 'string') {
								const translatedLabel = getTranslatedLabel(item);
								return {
									value: item,
									label: translatedLabel,
									originalLabel: item,
								};
							}

							// If item is an object, use specified keys or defaults
							const valueKey = props.params.valueKey || 'id' || 'value';
							const labelKey = props.params.labelKey || 'name' || 'label';
							const originalLabel = item[labelKey] || item[valueKey] || '';
							const translatedLabel = getTranslatedLabel(originalLabel);

							return {
								value: item[valueKey] || '',
								label: translatedLabel,
								originalLabel: originalLabel,
							};
						});
				} else {
					console.error('SelectField: API response is neither array nor object', data);
					options.value = [];
				}
				options.value.sort((a, b) => a.label.localeCompare(b.label));
			} catch (err) {
				console.error('SelectField: Error fetching options', err);
				error.value = err.message || 'Failed to load options';
				options.value = [];
			} finally {
				loading.value = false;
			}
		};

		// Watch for changes in record to update field value
		watch(
			() => props.record,
			() => {
				fieldValue.value = props.record[props.params.db_name];
				// Update search query for searchable selects
				if (props.params.searchable && fieldValue.value) {
					const selectedOption = options.value.find((opt) => opt.value === fieldValue.value);
					if (selectedOption) {
						searchQuery.value = selectedOption.label;
					}
				}
			},
			{ immediate: true, deep: false }
		);

		// Initialize search query for searchable selects
		watch(
			() => options.value,
			() => {
				if (props.params.searchable && fieldValue.value) {
					const selectedOption = options.value.find((opt) => opt.value === fieldValue.value);
					if (selectedOption) {
						searchQuery.value = selectedOption.label;
					}
				}
			}
		);

		onMounted(() => {
			if (props.params.endpoint) {
				fetchOptions();
			}
		});

		return {
			fieldValue,
			updateData,
			options,
			loading,
			error,
			searchQuery,
			showDropdown,
			filteredOptions,
			selectOption,
			handleBlur,
			clearSelection,
			handleBackspace,
		};
	},
});
</script>

<style lang="scss" scoped>
select {
	background-color: transparent;
	cursor: pointer;

	&.loading {
		cursor: wait;
	}

	&:disabled {
		cursor: not-allowed;
	}
}

option {
	color: $text-color;
}

.search-wrapper {
	width: 100%;
	position: relative;
}

.select-wrapper {
	width: 100%;
	position: relative;
}

.search-input {
	width: 100%;
	background-color: transparent;
	cursor: text;
	padding-right: 2.5rem; /* Make space for clear button */

	&:disabled {
		cursor: not-allowed;
	}
}

.input-text {
	padding-right: 2.5rem; /* Make space for clear button when clearable */
}

.clear-button {
	position: absolute;
	right: 0.5rem;
	top: 50%;
	transform: translateY(-50%);
	background: none;
	border: none;
	font-size: 1.2rem;
	cursor: pointer;
	color: #666;
	width: 1.5rem;
	height: 1.5rem;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	transition: all 0.2s ease;

	&:hover {
		background-color: rgba(0, 0, 0, 0.1);
		color: #333;
	}

	&:disabled {
		cursor: not-allowed;
		opacity: 0.5;
	}

	&.select-clear {
		z-index: 10; /* Ensure it's above the select element */
	}
}

.dropdown-list {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	border: 1px solid rgba(0, 0, 0, 0.1);
	border-top: none;
	border-radius: 0 0 4px 4px;
	max-height: 200px;
	overflow-y: auto;
	z-index: 1000;
	background-color: $background-color;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
	cursor: pointer;
	color: $text-color;
	font-size: 1rem * $phi-sr;
	padding: (1rem * $phi-down) 1rem;
	border-bottom: 1px solid rgba(0, 0, 0, 0.2);

	&:hover {
		background-color: rgba(0, 0, 0, 0.1);
	}

	&.selected {
		background-color: var(--primary-color, #007bff);
		color: white;
	}

	&.loading-item,
	&.no-results {
		color: var(--muted-color, #666);
		cursor: default;
		text-align: center;
		font-style: italic;

		&:hover {
			background-color: transparent;
		}
	}

	&:last-child {
		border-bottom: none;
	}
}
</style>
