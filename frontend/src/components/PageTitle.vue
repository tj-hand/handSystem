<template>
	<div class="page-title">
		{{ $t(params.label) }}
	</div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'PageTitle',
	props: {
		params: { type: Object },
	},
	setup() {},
});
</script>

<style scoped lang="scss">
.page-title {
	width: 100%;
	padding: 1rem;
	color: $primary-color;
	box-sizing: border-box;
	font-size: 1rem * $phi-up;
	background-color: #ffffff;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}
</style>
