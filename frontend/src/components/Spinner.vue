<template>
	<div
		class="spinner"
		v-if="uiStore.isLoading"
	></div>
</template>
<script>
import { defineComponent } from 'vue';
import { useUIStore } from '@/stores/useUIStore';

export default defineComponent({
	name: 'Spinner',
	setup() {
		const uiStore = useUIStore();
		return { uiStore };
	},
});
</script>

<style lang="scss" scoped>
.spinner {
	top: 0;
	left: 0;
	height: 4px;
	width: 100%;
	z-index: 9999;
	position: fixed;
	overflow: hidden;

	&::before {
		content: '';
		top: 0;
		left: 0;
		width: 50%;
		height: 100%;
		position: absolute;
		animation: horizontalSpinner 1.5s infinite ease-in-out;
		background: linear-gradient(to right, transparent, $primary-color, transparent);
	}
}

@keyframes horizontalSpinner {
	0% {
		transform: translateX(-100%);
	}
	100% {
		transform: translateX(100%);
	}
}
</style>
