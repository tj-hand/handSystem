<template>
	<div class="auth-layout">
		<Spinner />
		<LeftMenu />
		<main
			class="content"
			v-if="readyEnviroment"
		>
			<ScopeSelector class="scope-selector" />
			<RouterView class="router-view" />
		</main>
	</div>
</template>

<script>
import { ref } from 'vue';
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import Spinner from '@/components/Spinner.vue';
import { useUIStore } from '@/stores/useUIStore';
import LeftMenu from '@/layouts/Auth/LeftMenu/index.vue';
import ScopeSelector from '@/layouts/Auth/ScopeSelector/index.vue';
import { requestEnviroment } from '@/services/requestEnviromentService';

export default defineComponent({
	name: 'AuthLayout',
	components: {
		Spinner,
		LeftMenu,
		ScopeSelector,
	},
	setup() {
		const router = useRouter();
		const uiStore = useUIStore();
		const readyEnviroment = ref(false);

		onMounted(async () => {
			uiStore.isLogin ? uiStore.exitLoginProcess() : await requestEnviroment(router);
			readyEnviroment.value = true;
		});

		return { readyEnviroment };
	},
});
</script>

<style lang="scss">
.auth-layout {
	width: 100%;
	height: 100vh;
	display: flex;
	overflow: hidden;
	background-color: $background-color;

	.content {
		flex: 1;
		min-width: 0;
		display: flex;
		height: 100vh;
		flex-direction: column;

		.router-view {
			flex: 1;
			display: flex;
			min-height: 0;
			overflow: hidden;
			flex-direction: column;

			.content-wrapper {
				flex-grow: 1;
				display: flex;
				min-height: 0;
				flex-direction: row;

				.item-data {
					flex-grow: 1;
					display: flex;
					min-height: 0;
					flex-direction: column;
				}
			}
		}
	}
}

.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.5s ease;
}
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}
</style>
