<template>
	<div class="listbox-wrapper">
		<h2 class="listbox-title">{{ $t(params.title) }}</h2>

		<div class="listbox">
			<div class="list-header">
				<div class="search-wrapper">
					<div class="form-element-wrapper">
						<input
							type="text"
							v-model="searchQuery"
							:placeholder="$t('generic.search')"
						/>
						<div class="icon">search</div>
					</div>
				</div>

				<div
					class="actions"
					v-if="!supersmall"
				>
					<span
						class="icon-btn icon"
						@click="selectAll"
						>select_all</span
					>
					<span
						class="icon-btn icon"
						@click="deselectAll"
						>deselect</span
					>
					<span
						class="icon-btn icon"
						@click="toggleView"
					>
						{{ showOnlySelected ? 'visibility_off' : 'filter_list' }}
					</span>
				</div>
			</div>

			<ul class="item-list">
				<template
					v-for="item in visibleItems"
					:key="item.id"
				>
					<!-- Group Header -->
					<li
						v-if="item.isGroupHeader"
						class="group-header"
					>
						<div class="group-title">
							{{ item.needsTranslation ? $t('actions_set.' + item.title) : item.title }}
						</div>
					</li>
					<!-- Regular Item -->
					<li
						v-else
						class="item"
						@click="toggleItem(item.id)"
						:class="{
							selected: item.selected === true || item.selected === 'waiting',
							waiting: item.selected === 'waiting',
						}"
					>
						<span class="icon">
							{{
								item.selected === 'waiting'
									? 'hourglass_empty'
									: item.selected === true
									? 'check_box'
									: 'check_box_outline_blank'
							}}
						</span>

						<div class="item-content">
							<div class="title">
								{{ item.needsTranslation ? $t(item.title) : item.title }}
							</div>
							<div
								class="subtitle"
								v-if="item.subtitle"
							>
								{{ item.needsTranslation ? $t(item.subtitle) : item.subtitle }}
							</div>
						</div>
					</li>
				</template>
			</ul>
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
	name: 'CheckableItemList',
	props: {
		params: { type: Object },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const items = ref([]);
		const { t } = useI18n();
		const flatItems = ref([]);
		const route = useRoute();
		const searchQuery = ref('');
		const selectedIds = ref(new Set());
		const showOnlySelected = ref(false);
		const supersmall = ref(window.innerWidth < 480);

		// Check if data is hierarchical (has groups with actions)
		const isHierarchical = (data) => {
			return data && data.length > 0 && data[0].items_list && Array.isArray(data[0].items_list);
		};

		// Transform hierarchical data to flat structure with group headers
		const transformHierarchicalData = (hierarchicalData) => {
			const result = [];

			hierarchicalData.forEach((group, groupIndex) => {
				// Add group header
				result.push({
					id: `group-${groupIndex}`,
					title: group.title,
					isGroupHeader: true,
					needsTranslation: true,
				});

				// Add actions as items
				group.items_list.forEach((action) => {
					const uniqueId = action.id;
					result.push({
						id: uniqueId,
						title: action.title,
						subtitle: action.subtitle ? action.subtitle : null,
						selected: action.selected,
						groupId: group.id,
						originalAction: action,
						needsTranslation: true,
					});
				});
			});

			return result;
		};

		const getList = async () => {
			const backendPath = props.params.backend_request;
			const method = backendPath.split('.').reduce((acc, key) => acc?.[key], apiService);
			if (typeof method !== 'function') return;

			const { success, list } = await method({ id: route.params.id });
			if (!success) return;

			items.value = list;

			// Transform data based on structure
			if (isHierarchical(list)) {
				flatItems.value = transformHierarchicalData(list);
			} else {
				// Use original flat structure
				flatItems.value = list.map((item) => ({
					...item,
					isGroupHeader: false,
					needsTranslation: false,
				}));
			}

			// Initialize selected items
			flatItems.value.forEach((item) => {
				if (!item.isGroupHeader && (item.selected === true || item.selected === 'waiting')) {
					selectedIds.value.add(item.id);
				}
			});
		};

		const filteredItems = computed(() => {
			const query = searchQuery.value.toLowerCase();
			if (!query) return flatItems.value;

			const result = [];
			let currentGroupHeader = null;

			flatItems.value.forEach((item) => {
				if (item.isGroupHeader) {
					currentGroupHeader = item;
					return;
				}

				// Get translated text for search
				const titleText = item.needsTranslation ? t(item.title) : item.title;
				const subtitleText = item.subtitle ? (item.needsTranslation ? t(item.subtitle) : item.subtitle) : '';
				const inTitle = titleText.toLowerCase().includes(query);
				const inSubtitle = subtitleText.toLowerCase().includes(query);

				if (inTitle || inSubtitle) {
					// Add group header if not already added and we have items from this group
					if (currentGroupHeader && !result.some((r) => r.id === currentGroupHeader.id)) {
						result.push(currentGroupHeader);
					}
					result.push(item);
				}
			});

			return result;
		});

		const visibleItems = computed(() => {
			if (!showOnlySelected.value) return filteredItems.value;

			const result = [];
			let currentGroupHeader = null;

			filteredItems.value.forEach((item) => {
				if (item.isGroupHeader) {
					currentGroupHeader = item;
					return;
				}

				if (selectedIds.value.has(item.id)) {
					// Add group header if not already added and we have selected items from this group
					if (currentGroupHeader && !result.some((r) => r.id === currentGroupHeader.id)) {
						result.push(currentGroupHeader);
					}
					result.push(item);
				}
			});

			return result;
		});

		const updateData = () => {
			if (isHierarchical(items.value)) {
				const selectedSubItems = [];

				// Use flatItems to get the selection state since that's where we track selections
				flatItems.value.forEach((flatItem) => {
					if (!flatItem.isGroupHeader) {
						selectedSubItems.push({
							id: flatItem.id,
							selected: selectedIds.value.has(flatItem.id),
						});
					}
				});

				emit('updateData', {
					fieldName: props.params.backend_request,
					fieldValue: selectedSubItems,
				});
			} else {
				// For flat data, use original structure
				emit('updateData', {
					fieldName: props.params.backend_request,
					fieldValue: flatItems.value
						.filter((item) => !item.isGroupHeader)
						.map((item) => ({
							id: item.id,
							selected: selectedIds.value.has(item.id),
						})),
				});
			}
		};

		const toggleItem = (id) => {
			const item = flatItems.value.find((i) => i.id === id);
			if (!item || item.isGroupHeader) return;

			if (item.selected === true || item.selected === 'waiting') {
				item.selected = false;
				selectedIds.value.delete(id);
			} else {
				item.selected = true;
				selectedIds.value.add(id);
			}
			updateData();
		};

		const selectAll = () => {
			filteredItems.value.forEach((item) => {
				if (!item.isGroupHeader) {
					selectedIds.value.add(item.id);
					item.selected = true;
				}
			});
			updateData();
		};

		const deselectAll = () => {
			filteredItems.value.forEach((item) => {
				if (!item.isGroupHeader) {
					selectedIds.value.delete(item.id);
					item.selected = false;
				}
			});
			updateData();
		};

		const unselectAll = () => {
			flatItems.value.forEach((item) => {
				if (!item.isGroupHeader) {
					item.selected = false;
				}
			});
			selectedIds.value.clear();
			updateData();
		};

		const toggleView = () => {
			showOnlySelected.value = !showOnlySelected.value;
		};

		onMounted(async () => {
			await getList();
			if (route.params.id === 'new') deselectAll();
		});

		watch(
			() => route.params.id,
			(newId) => (newId == 'new' ? unselectAll() : null)
		);

		return {
			supersmall,
			searchQuery,
			visibleItems,
			showOnlySelected,
			selectAll,
			toggleView,
			toggleItem,
			deselectAll,
		};
	},
});
</script>

<style lang="scss" scoped>
.listbox-wrapper {
	width: 100%;

	.listbox-title {
		font-weight: bold;
		color: $text-color;
		margin-bottom: 1rem;
		font-size: 1rem * $phi-sr;
		padding-left: 1rem;
		padding-bottom: 1rem * $phi-down;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	}
}

.listbox {
	border-radius: 4px;
	background: rgba(255, 255, 255, 0.5);
	border: 1px solid rgba(0, 0, 0, 0.1);

	.list-header {
		display: flex;
		flex-direction: row;
		align-items: center;

		.search-wrapper {
			flex-grow: 1;
			display: flex;
			flex-direction: row;

			.form-element-wrapper {
				flex-grow: 1;
				display: flex;
				border-radius: 4px;
				margin-right: 1rem;
				align-items: center;
				box-sizing: border-box;
				transition: all 0.5s ease;
				padding: 1rem * $phi-down;
				justify-content: space-between;
				background: $form-wrapper-backgorund-color;
				border: 1px solid $form-wrapper-border-color;
				box-shadow: 2px 2px 5px inset rgba(0, 0, 0, 0.05);
				.icon {
					font-size: 1.25rem;
					color: $text-color;
				}
				input {
					border: 0;
					outline: 0;
					flex-grow: 1;
					color: $text-color;
					font-size: 1rem * $phi-sr;
					background-color: transparent;
				}
			}
		}

		.actions {
			gap: 1rem;
			display: flex;
			margin-right: 1rem;
			justify-content: flex-end;

			.icon-btn {
				cursor: pointer;
				color: $text-color;
				border-radius: 4px;
				font-size: 1rem * $phi-up;

				&:hover {
					background-color: rgba(0, 0, 0, 0.06);
				}
			}
		}
	}
}

.item-list {
	margin: 0;
	padding: 0;
	list-style: none;

	height: 12rem;
	overflow-y: auto;

	border-top: 1px solid rgba(0, 0, 0, 0.1);
	background-color: rgba(255, 255, 255, 0.9);
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);

	.group-header {
		display: flex;
		align-items: center;
		font-weight: bold;
		color: $text-color;
		font-size: 1rem * $phi-sr;
		padding: 1rem * $phi-down;
		background-color: rgba(0, 0, 0, 0.05);
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);

		.group-title {
			font-size: 0.9rem;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: rgba($text-color, 0.8);
		}
	}

	.item {
		display: flex;
		cursor: pointer;
		color: $text-color;
		padding-left: 2rem;
		align-items: center;
		gap: 1rem * $phi-sr;
		font-size: 1rem * $phi-sr;
		padding: 1rem * $phi-down;
		transition: all 0.2s ease;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);

		&:hover {
			background-color: rgba(0, 0, 0, 0.05);
		}

		&.selected {
			$color: $primary-color;
			background-color: rgba($primary-color, 0.2);

			.icon {
				color: $primary-color;
			}

			&.waiting {
				$color: $warning-color;
				background-color: rgba($warning-color, 0.2);

				.icon {
					color: $warning-color;
				}
			}
		}

		.icon {
			flex-shrink: 0;
			font-size: 1rem * $phi-up;
		}

		.item-content {
			.title {
				font-weight: bold;
			}

			.subtitle {
				font-size: 1rem * $phi-sr;
			}
		}
	}
}
</style>
