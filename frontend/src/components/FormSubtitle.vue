<template>
	<div class="form-subtitle">
		{{ $t(params.label) }}
	</div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'FormSubtitle',
	props: {
		params: { type: Object },
	},
	setup() {},
});
</script>

<style scoped lang="scss">
.form-subtitle {
	width: 100%;
	margin-top: 1rem;
	margin-bottom: 1rem;
	font-weight: bold;
	color: $text-color;
	font-size: 1rem $phi-sr;
	padding-bottom: 1rem * $phi-down;
	border-bottom: 1px solid rgba(0, 0, 0, 0.2);
}
</style>
