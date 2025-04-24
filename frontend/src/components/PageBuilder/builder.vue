<template>
	<div
		class="row"
		v-if="schema.type === 'row'"
	>
		<div
			class="col"
			:key="index"
			v-for="(column, index) in schema.cols"
			:style="'width: ' + column.width + '%;'"
		>
			<BuilderCore
				@updateData="updateFormData"
				v-bind="{ schema: column.content, formData, formSize }"
			/>
		</div>
	</div>
	<component
		@updateData="updateFormData"
		:is="schema.props.component"
		v-if="schema.type === 'field' && schema.props.component"
		v-bind="{ formSize, formData, params: schema.props || {} }"
	/>
</template>

<script>
import { ref } from 'vue';
import { defineComponent } from 'vue';
import TextField from '@/components/TextField.vue';
import PasswordField from '@/components/PasswordField.vue';

export default defineComponent({
	name: 'BuilderCore',
	components: {
		TextField,
		PasswordField,
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
	width: 100%;
	display: flex;
	flex-wrap: wrap;

	.col {
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}
}
</style>
