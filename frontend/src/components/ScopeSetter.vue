<template>
	<div class="scope-setter">
		<label class="label">{{ $t(params.label) }}</label>
		<div class="selectors">
			<div
				class="button primary"
				@click="setScope('account')"
				:class="fieldValue === 'account' ? 'primary' : 'neutral'"
			>
				{{ $t('generic.account') }}
			</div>
			<div
				class="button primary"
				@click="setScope('client')"
				:class="fieldValue === 'client' ? 'primary' : 'neutral'"
			>
				{{ $t('generic.client') }}
			</div>
		</div>
	</div>
</template>
<script>
import { ref } from 'vue';
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'ScopeSetter',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const fieldValue = ref(props.record[props.params.db_name]);

		const setScope = (scope) => {
			fieldValue.value = scope;
			updateData();
		};

		const updateData = () => {
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: fieldValue.value,
			});
		};

		return {
			fieldValue,
			setScope,
		};
	},
});
</script>

<style lang="scss" scoped>
.selectors {
	display: flex;
	gap: 1rem * $phi;
	max-width: 12rem;
	flex-direction: column;
	margin-top: 1rem * $phi-down;

	.button {
		border: 0;
		padding: (1rem * $phi) 0;
	}
}
</style>
