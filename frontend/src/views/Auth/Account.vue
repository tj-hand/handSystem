<template>
	<div class="account">
		<PageBuilder
			:grow="true"
			v-if="account"
			:key="builderKey"
			:schema="account"
			:formData="formData"
			ref="formGeneratorRef"
		/>
	</div>
</template>

<script>
import { ref } from 'vue';
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import account from '@/pagebuilder/account.json';
import { useAuthStore } from '@/stores/useAuthStore';
import PageBuilder from '@/components/PageBuilder/index.vue';

export default defineComponent({
	name: 'Settings',
	components: {
		PageBuilder,
	},
	setup() {
		const formData = ref([]);
		const builderKey = ref(0);
		const authStore = useAuthStore();

		const getAccount = async () => {
			const response = await apiService.account.show({ id: authStore.enviroment.current_scope.account_id });
			if (response.success) {
				formData.value = response.account;
				builderKey.value++;
			}
		};

		onMounted(() => {
			getAccount();
		});

		return { formData, account, builderKey };
	},
});
</script>

<style scoped lang="scss">
.account {
	margin: (1rem * $phi-down) 1rem;
}
</style>
