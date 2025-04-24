<template>
	<div @click="logout">Welcome View</div>
</template>

<script>
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import { logoutService } from '@/services/logoutService';

export default defineComponent({
	name: 'Welcome',
	setup() {
		const router = useRouter();

		const logout = async () => {
			logoutService(router);
		};

		return { logout };
	},
});
</script>
