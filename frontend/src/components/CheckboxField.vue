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
		<div class="form-element-error"></div>
	</div>
</template>

<script>
import { ref, watch } from 'vue';
import Toggle from '@vueform/toggle';
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'CheckboxField',
	components: { Toggle },
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

@media only screen and (max-width: 480px) {
	.label {
		text-align: center;
		padding-left: 0 !important;
	}
	.form-element-wrapper {
		&.checkbox {
			justify-content: center !important;
		}
	}
}
</style>
