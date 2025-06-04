<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<PageTitle title="generic.users" />
		<div class="data-container">
			<SelectList
				:list="list"
				:width="300"
				idField="uuid"
				mainContent="name"
				subContent="email"
				@addAction="addAction"
				:addPermission="can('add')"
				title="auth.users.local_users"
				v-if="isListVisible && can('list')"
			/>
			<div class="item-data">
				<NoRecord v-if="isNoRecordVisible" />
				<ObjectCard
					:record="record"
					title_db_name="name"
					subtitle_db_name="uuid"
					v-if="isObjectCardVisible && can('show')"
					title_placeholder="auth.user.title_placeholder"
					subtitle_placeholder="auth.user.subtitle_placeholder"
				>
					<template #form>
						<PageBuilder
							:record="record"
							:schema="localUsers"
							ref="formGeneratorRef"
							@buttonAction="buttonAction"
							@update:record="updateRecord"
						/>
					</template>
					<template #action>
						<ActionsBar
							@action="mainAction"
							@cancel="cancelAction"
							@delete="showDeleteConfirmation"
							:cancelButton="$t('generic.cancel')"
							:actionButton="can('edit') ? $t('generic.save') : null"
							:deleteButton="can('delete') ? $t('generic.delete') : null"
						/>
					</template>
				</ObjectCard>
			</div>
		</div>

		<ModalConfirmation
			v-model="showModal"
			@confirm="modalMethod"
			:isDanger="modalDanger"
			:title="modalTitle ? $t(modalTitle) : ''"
			:message="modalText ? $t(modalText) : ''"
			:cancelButton="modalCancel ? $t(modalCancel) : ''"
			:confirmButton="modalConfirm ? $t(modalConfirm) : ''"
		/>
	</div>
</template>
<script>
import { defineComponent } from 'vue';
import { isUUID } from '@/tools/isUUID';
import apiService from '@/api/apiService';
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { onMounted, onBeforeUnmount } from 'vue';
import NoRecord from '@/components/NoRecord.vue';
import PageTitle from '@/components/PageTitle.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import ObjectCard from '@/components/ObjectCard.vue';
import { is_smallScreen } from '@/tools/screenSizes';
import SelectList from '@/components/SelectList.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import { validators } from '@/validations/validators';
import localUsers from '@/pagebuilder/localUsers.json';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

export default defineComponent({
	name: 'LocalUsers',
	components: {
		NoRecord,
		PageTitle,
		ActionsBar,
		SelectList,
		ObjectCard,
		PageBuilder,
		ModalConfirmation,
	},
	setup() {
		const list = ref([]);
		const route = useRoute();
		const modalText = ref('');
		const modalTitle = ref('');
		const router = useRouter();
		const modalDanger = ref('');
		const modalCancel = ref('');
		const modalMethod = ref('');
		const showModal = ref(false);
		const modalConfirm = ref('');
		const authStore = useAuthStore();
		const recordIsLoaded = ref(false);
		const formGeneratorRef = ref(null);
		const record = formGuardService.getrecord();

		const isListVisible = computed(() => {
			if (!is_smallScreen()) return true;
			const isEditing = recordIsLoaded.value && isUUID(route.params.id);
			return !isCreatingNew.value && !isEditing;
		});

		const isCreatingNew = computed(() => {
			return route.params.id === 'new';
		});

		const isNoRecordVisible = computed(() => {
			return !(
				is_smallScreen() ||
				isCreatingNew.value ||
				(recordIsLoaded.value && Object.keys(record.value).length > 0)
			);
		});

		const isObjectCardVisible = computed(() => {
			const hasLoadedExisting = recordIsLoaded.value && isUUID(route.params.id);
			return hasLoadedExisting || isCreatingNew.value;
		});

		const updateRecord = (newValue) => {
			record.value = { ...newValue };
		};

		const addAction = () => updateRouteParams({ ...route.params, id: 'new' });

		const cancelAction = () => {
			recordIsLoaded.value = false;
			updateRouteParams({ ...route.params, id: null });
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
						if (isCreatingNew.value) await updateRouteParams({ ...route.params, id: user.record.uuid });
						showToast(message);
						getList();
					} else {
						showToast(message, { type: 'error' });
					}
				}
			}
		};

		const showDeleteConfirmation = () => {
			showModal.value = true;
			modalDanger.value = true;
			modalMethod.value = deleteAction;
			modalCancel.value = 'generic.cancel';
			modalConfirm.value = 'generic.delete';
			modalTitle.value = 'generic.confirm_delete';
			modalText.value = 'generic.delete_confirmation';
		};

		const deleteAction = async () => {
			const { success, message } = await apiService.user.delete({ id: record.value.uuid });
			if (success) {
				cancelAction();
				formGuardService.setOriginal({});
				getList();
				return;
			} else {
				showToast(message, { type: 'error' });
			}
		};

		const updateRouteParams = async (newParams) => {
			const failed = await router.push({
				name: route.name,
				params: newParams,
			});
			return failed === false;
		};

		const getList = async () => {
			const { success, users } = await apiService.client.local_users();
			if (success) list.value = users;
		};

		const getRecord = async () => {
			recordIsLoaded.value = false;
			const hasValidId = isUUID(route.params.id);
			if (!hasValidId && route.params.id !== 'new') return;
			let success = route.params.id !== 'new' ? false : true;
			let recordData = {
				is_active_to_account: true,
				is_superuser: false,
				is_blocked: false,
				is_account_admin: false,
			};
			if (hasValidId) {
				const { success: apiSuccess, user } = await apiService.user.show({ id: route.params.id });
				success = apiSuccess;
				recordData = success
					? user.record ?? {
							is_active_to_account: true,
							is_superuser: false,
							is_blocked: false,
							is_account_admin: false,
					  }
					: { is_active_to_account: true, is_superuser: false, is_blocked: false, is_account_admin: false };
			}
			formGuardService.setOriginal(recordData);
			recordIsLoaded.value = true;
			if (!success) cancelAction();
		};

		const addUserToClient = async () => {
			const email = record.value.email?.trim();
			const { success, message, user } = await apiService.user.addToClient({ email: email });
			if (success) {
				formGuardService.setOriginal(user.record);
				if (isCreatingNew.value) await updateRouteParams({ ...route.params, id: user.record.uuid });
				showToast(message);
				getList();
			} else {
				showToast(message, { type: 'error' });
			}
		};

		const checkUser = async () => {
			if (!isCreatingNew.value) return;
			const email = record.value.email?.trim();
			if (!email || !validators.email(email)) return;
			const { success, user } = await apiService.user.existsInClient({ email: email });
			if (!success) return;

			switch (user) {
				case 'exists':
					showModal.value = true;
					modalDanger.value = false;
					modalCancel.value = 'generic.no';
					modalConfirm.value = 'generic.yes';
					modalMethod.value = addUserToClient;
					modalText.value = 'auth.user.modal.add_to_client_text';
					modalTitle.value = 'auth.user.modal.add_to_account_title';
					break;
				case 'blocked':
					showModal.value = true;
					modalMethod.value = '';
					modalDanger.value = true;
					modalConfirm.value = 'generic.OK';
					modalText.value = 'auth.user.modal.blocked_user_text';
					modalTitle.value = 'auth.user.modal.blocked_user_title';
					break;
				case 'inAccount':
					showModal.value = true;
					modalDanger.value = false;
					modalCancel.value = 'generic.no';
					modalConfirm.value = 'generic.yes';
					modalMethod.value = addUserToClient;
					modalText.value = 'auth.user.modal.add_to_client_text';
					modalTitle.value = 'auth.user.modal.in_account_title';
					break;
				case 'inClient':
					modalCancel.value = '';
					showModal.value = true;
					modalMethod.value = '';
					modalDanger.value = false;
					modalConfirm.value = 'generic.OK';
					modalText.value = 'auth.user.modal.in_client_text';
					modalTitle.value = 'auth.user.modal.in_client_title';
					break;
				default:
					break;
			}
		};

		const can = (action) => {
			return authStore.enviroment.permissions.LocalUsers.some(
				(p) => p.identifier === 'auth.local_users.' + action
			);
		};

		const buttonAction = (action) => {
			if (action == 'send_invite') {
				showModal.value = true;
				modalDanger.value = false;
				modalMethod.value = sendInvite;
				modalCancel.value = 'generic.cancel';
				modalConfirm.value = 'generic.OK';
				modalText.value = 'auth.user.modal.confirm_invite_text';
				modalTitle.value = 'auth.user.modal.confirm_invite_title';
			}
		};

		const sendInvite = async () => {
			const { success } = await apiService.user.sendInvite({ id: route.params.id });
			success ? showToast(message) : showToast(message, { type: 'error' });
		};

		watch(
			() => route.params.id,
			() => getRecord(),
			{ immediate: true }
		);

		watch(
			() => record.value.email,
			() => checkUser(),
			{ immediate: true }
		);

		onMounted(async () => {
			getList();
			getRecord();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});

		return {
			list,
			record,
			showModal,
			modalText,
			localUsers,
			modalTitle,
			modalDanger,
			modalCancel,
			modalMethod,
			modalConfirm,
			isListVisible,
			formGeneratorRef,
			isNoRecordVisible,
			isObjectCardVisible,
			can,
			addAction,
			mainAction,
			sendInvite,
			buttonAction,
			deleteAction,
			updateRecord,
			cancelAction,
			showDeleteConfirmation,
		};
	},
});
</script>

<style scoped lang="scss">
.content-wrapper {
	.data-container {
		flex-grow: 1;
		display: flex;
		flex-direction: row;
		.item-data {
			flex-grow: 1;
			display: flex;
		}
	}
}
</style>
