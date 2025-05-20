<template>
	<div
		v-if="render"
		:class="['checkbox-field', labelPositionClass, formSizeClass]"
	>
		<label
			v-if="isTopLabel"
			class="label"
			>{{ $t(params.label) }}</label
		>

		<div class="form-element-row">
			<div
				class="form-element-wrapper checkbox"
				:class="wrapperClass"
			>
				<Toggle
					v-model="fieldValue"
					@change="updateData"
					:offLabel="$t('generic.no')"
					:onLabel="$t('generic.yes')"
				/>
				<label
					v-if="isRightLabel"
					class="label label-right"
					>{{ $t(params.label) }}</label
				>
			</div>
			<div class="form-element-error"></div>
		</div>
	</div>
</template>

<script>
import Toggle from '@vueform/toggle';
import { defineComponent } from 'vue';
import { ref, watch, computed } from 'vue';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'CheckboxField',
	components: { Toggle },
	props: {
		params: { type: Object, required: true },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const authStore = useAuthStore();
		const fieldValue = ref(props.record[props.params.db_name]);

		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		watch(
			() => props.record,
			() => {
				fieldValue.value = props.record[props.params.db_name];
			},
			{ immediate: true }
		);

		const render = computed(() => {
			const validation = props.params.validations;
			if (!validation) return true;
			const user = authStore.enviroment?.user;
			if (!user) return true;
			const rules = {
				superuser: user.superuser === true,
				account_admin: user.account_admin === true,
			};
			return rules[validation] ?? true;
		});

		const isTopLabel = computed(() => labelPosition.value === 'top');
		const isRightLabel = computed(() => labelPosition.value === 'right');
		const labelPosition = computed(() => props.params.labelPosition || 'top');
		const labelPositionClass = computed(() => `label-${labelPosition.value}`);
		const formSizeClass = computed(() => (props.formSize === 'max' ? 'max' : ''));
		const wrapperClass = computed(() => ({ 'with-right-label': labelPosition.value === 'right' }));

		return {
			render,
			fieldValue,
			isTopLabel,
			isRightLabel,
			wrapperClass,
			formSizeClass,
			labelPositionClass,
			updateData,
		};
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

.checkbox-field.label-top .label {
	display: block;
	margin-bottom: 0.5rem;
}

.checkbox-field.label-right .form-element-wrapper.checkbox.with-right-label {
	gap: 0.5rem;
	align-items: center;
	display: inline-flex;
	justify-content: flex-start;
}

.label-right {
	margin: 0;
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
	.label,
	.label-right {
		text-align: center;
		padding-left: 0 !important;
	}
}
</style>
