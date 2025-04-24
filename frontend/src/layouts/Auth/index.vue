<template>
	<div class="auth-layout">
		<Spinner />
		<LeftMenu />
		<RouterView />
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import Spinner from '@/components/Spinner.vue';
import { useUIStore } from '@/stores/useUIStore';
import LeftMenu from '@/layouts/Auth/LeftMenu/index.vue';
import { getProfile } from '@/services/userProfileService';

export default defineComponent({
	name: 'AuthLayout',
	components: {
		Spinner,
		LeftMenu,
	},
	setup() {
		const router = useRouter();
		const uiStore = useUIStore();

		onMounted(async () => {
			uiStore.isLogin ? uiStore.exitLoginProcess() : await getProfile(router);
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
}
</style>
