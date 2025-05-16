<template>
	<div class="content-wrapper">
		<ObjectCard
			width="50%"
			class="itemData"
			v-if="!isLoading"
			:record="record"
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
					@delete="deleteAction"
					:actionButton="$t('generic.save')"
					:deleteButton="$t('generic.delete')"
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

		const getAccount = async () => {
			isLoading.value = true;
			const response = await apiService.account.show({ id: authStore.enviroment.current_scope.account_id });
			if (response.success) {
				formGuardService.setOriginal(response.account.record);
				record.value = formGuardService.getPlainrecord();
				isLoading.value = false;
			}
		};

		const action = async () => {
			const validationResult = formGeneratorRef.value.validate();
			if (validationResult) {
				const response = await apiService.account.upsert(record.value);
				if (response.success) {
					showToast(response.message);
					formGuardService.setOriginal(response.account);
					uiStore.setDirtyForm(false);
				} else {
					showToast(response.message, { type: 'error' });
				}
			}
		};

		const deleteAction = () => {
			console.log('delete');
		};

		onMounted(() => {
			getAccount();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});

		return {
			account,
			record,
			isLoading,
			formGeneratorRef,
			action,
			deleteAction,
		};
	},
});
</script>

<style scoped lang="scss"></style>
