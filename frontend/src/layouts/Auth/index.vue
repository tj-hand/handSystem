<template>
	<div class="auth-layout">
		<Spinner />
		<LeftMenu />
		<main class="content">
			<RouterView />
		</main>
		<ScopeSelector />
	</div>
</template>

<script>
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

		onMounted(async () => {
			uiStore.isLogin ? uiStore.exitLoginProcess() : await requestEnviroment(router);
		});
	},
});
</script>

<style lang="scss">
.auth-layout {
	flex-grow: 1;
	display: flex;
	height: 100vh;
	position: relative;
	background-color: $background-color;

	.content {
		flex: 1;
		overflow: auto;
	}
}
</style>
