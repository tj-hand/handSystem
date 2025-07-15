<template>
	<div
		v-if="can"
		class="content-wrapper"
	>
		<ObjectCard
			class="itemData"
			:record="record"
			v-if="!isLoading"
			title_db_name="name"
			subtitle_db_name="id"
			title_placeholder="auth.account.title_placeholder"
			subtitle_placeholder="auth.account.subtitle_placeholder"
		>
			<template #form>
				<PageBuilder
					:schema="account"
					:record="record"
					ref="formGeneratorRef"
				/>
			</template>
			<template #action>
				<ActionsBar
					@action="action"
					@cancel="cancelAction"
					:actionButton="$t('generic.save')"
					:cancelButton="$t('generic.cancel')"
				/>
			</template>
		</ObjectCard>
	</div>
</template>

<script>
import { ref } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import apiService from '@/api/apiService';
import { onMounted, onBeforeUnmount } from 'vue';
import account from '@/pagebuilder/account.json';
import { useUIStore } from '@/stores/useUIStore';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';

export default defineComponent({
	name: 'Account',
	components: {
		ObjectCard,
		ActionsBar,
		PageBuilder,
	},
	setup() {
		const record = ref([]);
		const router = useRouter();
		const isLoading = ref(true);
		const uiStore = useUIStore();
		const authStore = useAuthStore();
		const formGeneratorRef = ref(null);

		const can = authStore.enviroment.user.superuser;

		const action = async () => {
			const validationResult = formGeneratorRef.value.validate();
			if (validationResult) {
				const response = await apiService.account.upsert(record.value);
				if (response.success) {
					showToast(response.message);
					formGuardService.setOriginal(response.account);
					uiStore.setDirtyForm(false);
					await cancelAction();
					window.location.reload();
				} else {
					showToast(response.message, { type: 'error' });
				}
			}
		};

		const getAccount = async () => {
			isLoading.value = true;
			formGuardService.setOriginal({
				is_active: true,
				client_users: false,
				user_global_actions: false,
			});
			record.value = formGuardService.getPlainrecord();
			isLoading.value = false;
		};

		const cancelAction = async () => {
			await router.push({ name: 'Account' });
		};

		onMounted(() => {
			getAccount();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});

		return {
			can,
			record,
			account,
			isLoading,
			formGeneratorRef,
			action,
			cancelAction,
		};
	},
});
</script>

<style scoped lang="scss"></style>
