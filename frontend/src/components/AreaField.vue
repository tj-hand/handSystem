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
			<textarea
				type="text"
				class="text-area"
				:rows="params.rows"
				@input="updateData"
				v-model="fieldValue"
				style="resize: none"
			>
			</textarea>
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
import { ref, watch } from 'vue';
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'TextField',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const fieldValue = ref(props.record[props.params.db_name]);
		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		watch(
			() => props.record,
			() => (fieldValue.value = props.record[props.params.db_name]),
			{ immediate: true, deep: false }
		);

		return { fieldValue, updateData };
	},
});
</script>

<style lang="scss" scoped>
input {
	background-color: transparent;
}
</style>
