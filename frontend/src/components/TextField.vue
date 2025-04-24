<template>
	<label
		class="label"
		:class="[formSize == 'max' ? 'max' : '']"
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
import { defineComponent, ref } from 'vue';

export default defineComponent({
	name: 'TextField',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		formData: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const fieldValue = ref(props.formData[props.params.db_name]);
		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};
		return { fieldValue, updateData };
	},
});
</script>

<style lang="scss" scoped>
input {
	background-color: transparent;
}
</style>
