<template>
	<div
		class="page-builder"
		:class="{ grow: grow }"
	>
		<Builder
			:key="index"
			:schema="row"
			:record="record"
			:formSize="formSize"
			@updateData="updaterecord"
			v-for="(row, index) in updatedSchema"
		/>
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Builder from './builder.vue';
import { defineComponent } from 'vue';
import { useUIStore } from '@/stores/useUIStore';
import { validateForm } from '@/services/formValidationService';

export default defineComponent({
	name: 'PageBuilder',
	components: { Builder },
	props: {
		grow: { type: Boolean, default: true },
		formSize: { type: String, default: '' },
		schema: { type: Object, required: true },
		record: { type: Object, required: true },
	},
	emits: ['buttonAction'],
	setup(props, { emit }) {
		const { t } = useI18n();
		const uiStore = useUIStore();
		const updatedData = ref(props.record);
		const updatedSchema = ref(props.schema);

		const updaterecord = (recordUpdated) => {
			if (recordUpdated.fieldName == 'button') {
				emit('buttonAction', recordUpdated.fieldValue);
			} else {
				uiStore.setDirtyForm(true);
				updatedData.value[recordUpdated.fieldName] = recordUpdated.fieldValue;
			}
		};

		const validate = () => {
			const result = validateForm(props.schema, updatedData.value, t);
			updatedSchema.value = result.formWithErrors;
			return result.valid ? true : false;
		};

		watch(
			() => props.record,
			(newVal) => (updatedData.value = newVal),
			{ immediate: true, deep: false }
		);

		onMounted(() => {
			uiStore.setDirtyForm(false);
		});

		return { validate, updaterecord, updatedData, updatedSchema };
	},
});
</script>

<style lang="scss" scoped>
.page-builder {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	&.grow {
		flex-grow: 1;
	}
}
</style>
