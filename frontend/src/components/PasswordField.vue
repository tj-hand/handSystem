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
			<div class="input-wrapper">
				<input
					class="input-text"
					@blur="updateData"
					v-model="fieldValue"
					:type="isPasswordVisible ? 'text' : 'password'"
				/>
				<span
					class="icon icon-wrapper"
					@click="togglePasswordVisibility"
				>
					<span v-if="isPasswordVisible">visibility_off</span>
					<span v-else>visibility</span>
				</span>
			</div>
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
	name: 'PasswordField',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const isPasswordVisible = ref(false);
		const fieldValue = ref(props.record[props.params.db_name]);

		const togglePasswordVisibility = () => {
			isPasswordVisible.value = !isPasswordVisible.value;
		};

		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		return {
			fieldValue,
			isPasswordVisible,
			updateData,
			togglePasswordVisibility,
		};
	},
});
</script>

<style lang="scss" scoped>
.input-wrapper {
	width: 100%;
	display: flex;
	position: relative;
	color: $text-color;
	align-items: center;

	.inputText {
		width: 100%;
		padding-right: 2rem;
		box-sizing: border-box;
	}

	.icon-wrapper {
		right: 0;
		display: flex;
		cursor: pointer;
		position: absolute;
		padding-right: 1rem;
		align-items: center;
		font-size: calc(1rem + 6px);
	}
}
</style>
