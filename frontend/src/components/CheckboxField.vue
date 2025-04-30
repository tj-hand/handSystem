<template>
	<label
		class="label"
		:class="[formSize == 'max' ? 'max' : '']"
		>{{ $t(params.label) }}</label
	>
	<div class="form-element-row">
		<div class="form-element-wrapper checkbox">
			<Toggle
				v-model="fieldValue"
				@change="updateData"
				:offLabel="$t('generic.no')"
				:onLabel="$t('generic.yes')"
			/>
		</div>
	</div>
</template>

<script>
import Toggle from '@vueform/toggle';
import { defineComponent, ref } from 'vue';

export default defineComponent({
	name: 'CheckboxField',
	components: { Toggle },
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

<style src="@vueform/toggle/themes/default.css"></style>

<style lang="scss" scoped>
.toggle-container {
	--toggle-width: 3.5rem;
	--toggle-bg-on: #0e62a4;
	--toggle-border-on: #0e62a4;
	--toggle-ring-color: #10b98100;
}

.checkbox {
	display: flex;
	height: 2.3rem;
	align-items: center;
	border: 0 !important;
	padding-left: 0 !important;
	box-shadow: none !important;
	background-color: transparent !important;
}
</style>
