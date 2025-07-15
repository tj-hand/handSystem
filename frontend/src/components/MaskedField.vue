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
			<input
				type="text"
				class="input-text"
				@blur="updateData"
				v-model="fieldValue"
				v-imask="maskOptions"
				@accept="onAccept"
				@complete="onComplete"
				:disabled="disabled"
				:class="[disabled ? 'disabled' : '']"
				:name="$t(params.label).replaceAll(' ', '_')"
			/>
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
import { useRoute } from 'vue-router';
import { ref, computed, watch } from 'vue';
import { defineComponent } from 'vue';
import { IMaskDirective } from 'vue-imask';
import IMask from 'imask';

export default defineComponent({
	name: 'MaskedField',
	directives: {
		imask: IMaskDirective,
	},
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const route = useRoute();
		const fieldValue = ref(props.record[props.params.db_name]);

		const disabled = computed(() => {
			return props.params.disabled == 'onEdit' && route.params.id != 'new' ? true : false;
		});

		// Mask options computed from params
		const maskOptions = computed(() => {
			if (!props.params.mask) return {};

			// If mask is a string, check if it's "Date" for date validation
			if (typeof props.params.mask === 'string') {
				if (props.params.mask === 'Date' || props.params.mask === 'date') {
					return {
						mask: Date,
						pattern: 'd{/}m{/}Y', // Brazilian format DD/MM/YYYY
						lazy: props.params.maskLazy || false,
						placeholderChar: props.params.placeholderChar || '_',
						blocks: {
							d: {
								mask: IMask.MaskedRange,
								from: 1,
								to: 31,
								maxLength: 2,
							},
							m: {
								mask: IMask.MaskedRange,
								from: 1,
								to: 12,
								maxLength: 2,
							},
							Y: {
								mask: IMask.MaskedRange,
								from: 1900,
								to: 2099,
								maxLength: 4,
							},
						},
						format: function (date) {
							if (!date) return '';
							var day = date.getDate();
							var month = date.getMonth() + 1;
							var year = date.getFullYear();
							return [
								day.toString().padStart(2, '0'),
								month.toString().padStart(2, '0'),
								year.toString(),
							].join('/');
						},
						parse: function (str) {
							if (!str) return null;
							var parts = str.split('/');
							if (parts.length !== 3) return null;
							return new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
						},
					};
				}
				// For other string masks, treat as simple pattern
				return {
					mask: props.params.mask,
					lazy: props.params.maskLazy || false,
					placeholderChar: props.params.placeholderChar || '_',
				};
			}

			// If mask is an object, use it directly
			return props.params.mask;
		});

		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		// Handle mask events
		const onAccept = (e) => {
			// Update fieldValue when mask accepts input
			fieldValue.value = e.detail.value;
		};

		const onComplete = (e) => {
			// Optional: Handle when mask is completely filled
			console.log('Mask completed:', e.detail.value);
		};

		watch(
			() => props.record,
			() => (fieldValue.value = props.record[props.params.db_name]),
			{ immediate: true, deep: false }
		);

		return {
			disabled,
			fieldValue,
			updateData,
			maskOptions,
			onAccept,
			onComplete,
		};
	},
});
</script>

<style lang="scss" scoped>
input {
	color: $text-color;
	background-color: transparent;
}
</style>
