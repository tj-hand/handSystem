<template>
	<div
		class="button"
		@click="buttonAction"
		:class="params.class"
	>
		{{ $t(params.label) }}
	</div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'ButtonField',
	props: {
		params: { type: Object },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const buttonAction = () => {
			emit('updateData', {
				fieldName: 'button',
				fieldValue: props.params.action,
			});
		};
		return { buttonAction };
	},
});
</script>
