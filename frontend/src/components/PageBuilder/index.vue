<template>
	<div
		class="page-builder"
		:class="{ grow: grow }"
	>
		<Builder
			:key="index"
			:schema="row"
			:formData="formData"
			:formSize="formSize"
			@updateData="updateFormData"
			v-for="(row, index) in updatedSchema"
		/>
	</div>
</template>

<script>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Builder from './builder.vue';
import { defineComponent } from 'vue';
import { validateForm } from '@/services/formValidationService';

export default defineComponent({
	name: 'PageBuilder',
	components: { Builder },
	props: {
		grow: { type: Boolean, default: true },
		formSize: { type: String, default: '' },
		schema: { type: Object, required: true },
		formData: { type: Object, required: true },
	},
	setup(props) {
		const { t } = useI18n();
		const updatedData = ref(props.formData);
		const updatedSchema = ref(props.schema);

		const updateFormData = (formDataUpdated) => {
			updatedData.value[formDataUpdated.fieldName] = formDataUpdated.fieldValue;
		};

		const validate = () => {
			const result = validateForm(props.schema, updatedData.value, t);
			updatedSchema.value = result.formWithErrors;
			return result.valid ? true : false;
		};

		return { validate, updateFormData, updatedData, updatedSchema };
	},
});
</script>

<style lang="scss" scoped>
.page-builder {
	width: 100%;
	display: flex;
	flex-direction: column;
	&.grow {
		flex-grow: 1;
	}
}
</style>
