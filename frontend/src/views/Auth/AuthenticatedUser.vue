<template>
	<div class="settings">
		<PageTitle title="auth.left_menu.user_menu.access_settings" />
		<ObjectCard
			width="50%"
			:record="record"
			title_db_name="name"
			subtitle_db_name="uuid"
			title_placeholder="auth.user.title_placeholder"
			subtitle_placeholder="auth.user.subtitle_placeholder"
		>
			<template #form>
				<PageBuilder
					:record="record"
					ref="formGeneratorRef"
					:schema="userAutheticated"
					@update:record="updateRecord"
				/>
			</template>
			<template #action>
				<ActionsBar
					@action="mainAction"
					:actionButton="$t('generic.save')"
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
import PageTitle from '@/components/PageTitle.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';
import userAutheticated from '@/pagebuilder/userAuthenticated.json';

export default defineComponent({
	name: 'AuthenticatedUser',
	components: {
		PageTitle,
		ActionsBar,
		ObjectCard,
		PageBuilder,
	},
	setup() {
		const router = useRouter();
		const authStore = useAuthStore();
		const formGeneratorRef = ref(null);
		const record = formGuardService.getrecord();

		const getRecord = () => {
			const recordData = {
				password: '',
				id: authStore.enviroment.user.id,
				uuid: authStore.enviroment.user.uuid,
				email: authStore.enviroment.user.email,
				user_name: authStore.enviroment.user.name,
				user_lastname: authStore.enviroment.user.lastname,
				name: authStore.enviroment.user.name + ' ' + authStore.enviroment.user.lastname,
			};
			formGuardService.setOriginal(recordData);
		};

		const updateRecord = (newValue) => {
			record.value = { ...newValue };
		};

		const mainAction = async () => {
			if (formGuardService.isDirty()) {
				const validationResult = formGeneratorRef.value.validate();
				if (validationResult) {
					const { success, message, user } = await apiService.user.upsert({
						record: record.value,
						scope: 'account',
					});
					if (success) {
						formGuardService.setOriginal(user.record);
						showToast(message);
					} else {
						showToast(message, { type: 'error' });
					}
				}
			}
		};

		onMounted(() => {
			getRecord();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});

		return { record, userAutheticated, formGeneratorRef, mainAction, updateRecord };
	},
});
</script>

<style scoped lang="scss"></style>
