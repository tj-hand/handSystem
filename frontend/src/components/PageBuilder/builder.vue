<template>
	<div
		class="row"
		:class="schema.class"
		v-if="schema.type === 'row'"
	>
		<div
			class="col"
			:key="index"
			v-for="(column, index) in schema.cols"
			:style="'width: ' + column.width"
		>
			<BuilderCore
				@updateData="updateFormData"
				v-bind="{ schema: column.content, formData, formSize }"
			/>
		</div>
	</div>
	<RouterView v-if="schema.type === 'field' && schema.props?.component == 'virtualRouter'" />
	<component
		@updateData="updateFormData"
		:is="schema.props.component"
		v-bind="{ formSize, formData, params: schema.props || {} }"
		v-else-if="schema?.type === 'field' && schema?.props?.component"
	/>
</template>

<script>
import { defineComponent } from 'vue';
import PageTitle from '@/components/PageTitle.vue';
import TextField from '@/components/TextField.vue';
import AreaField from '@/components/AreaField.vue';
import ObjectTitle from '@/components/ObjectTitle.vue';
import PasswordField from '@/components/PasswordField.vue';
import CheckboxField from '@/components/CheckboxField.vue';
import HorizontalSubmenu from '@/components/HorizontalSubmenu.vue';

export default defineComponent({
	name: 'BuilderCore',
	components: {
		PageTitle,
		AreaField,
		TextField,
		ObjectTitle,
		PasswordField,
		CheckboxField,
		HorizontalSubmenu,
	},
	props: {
		formSize: { type: String, default: '' },
		schema: { type: Object, required: true },
		formData: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const updateFormData = (formDataUpdated) => {
			emit('updateData', formDataUpdated);
		};

		return { updateFormData };
	},
});
</script>

<style lang="scss" scoped>
.row {
	gap: 0 1rem;
	width: 100%;
	display: flex;
	flex-wrap: wrap;

	&.grow {
		flex-grow: 1;
	}

	.col {
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}
}
</style>
