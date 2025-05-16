<template>
	<div class="page-title">
		{{ $t(title) }}
	</div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'PageTitle',
	props: {
		title: { type: String },
	},
	setup() {},
});
</script>

<style scoped lang="scss">
.page-title {
	width: 100%;
	box-sizing: border-box;
	font-size: 1rem * $phi-up;
	background-color: #ffffff;
	padding: 1rem 0 0.38rem 2rem;
	color: rgba($primary-color, 0.75);
}
</style>
