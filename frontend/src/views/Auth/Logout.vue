<template>
	<div class="logout">{{ $t('auth.logout.exiting') }}</div>
</template>

<script>
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import { closeEnviroment } from '@/services/closeEnviromentService';

export default defineComponent({
	name: 'Logout',
	setup() {
		const router = useRouter();
		onMounted(() => {
			closeEnviroment(router);
		});
	},
});
</script>
