<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<SelectList
			:list="list"
			:width="300"
			idField="id"
			subContent="id"
			addIcon="change_circle"
			@addAction="handleSyncConfirmation"
			mainContent="local_name"
			:addPermission="can('sync')"
			title="auth.workspaces.list_title"
			v-if="isListVisible && can('list')"
		/>
		<div class="item-data">
			<NoRecord v-if="isNoRecordVisible" />
			<ObjectCard
				width="50%"
				:record="record"
				title_db_name="local_name"
				subtitle_db_name="id"
				v-if="isObjectCardVisible && can('show')"
				title_placeholder="auth.workspaces.title_placeholder"
				subtitle_placeholder="auth.workspaces.subtitle_placeholder"
			>
				<template #form>
					<PageBuilder
						:record="record"
						:schema="workspaces"
						ref="formGeneratorRef"
						@update:record="updateRecord"
					/>
				</template>
				<template #action>
					<ActionsBar
						@action="handleMainAction"
						@cancel="cancelAction"
						@delete="handleDeleteConfirmation"
						:cancelButton="$t('generic.cancel')"
						:actionButton="can('edit') ? $t('generic.save') : null"
						:deleteButton="can('delete') ? $t('generic.delete') : null"
					/>
				</template>
			</ObjectCard>
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
// Import from Vue
import { ref, watch } from 'vue';
import { defineComponent } from 'vue';
import { onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// Import Components and Services
import apiService from '@/api/apiService';
import NoRecord from '@/components/NoRecord.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import SelectList from '@/components/SelectList.vue';
import workspaces from '@/pagebuilder/workspaces.json';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

// Import composables
import { useList, usePermissions, useResponsiveView, useRecordManagement, useModalConfirmation } from '@/composables';

export default defineComponent({
	name: 'Groups',
	components: {
		NoRecord,
		ActionsBar,
		SelectList,
		ObjectCard,
		PageBuilder,
		ModalConfirmation,
	},
	setup() {
		const route = useRoute();
		const router = useRouter();
		const formGeneratorRef = ref(null);

		// Use composables
		const { can } = usePermissions('Workspaces');

		const { list, getList } = useList(apiService.account.workspaces, 'workspaces');

		const {
			record,
			recordIsLoaded,
			updateRecord,
			cancelAction,
			getRecord,
			mainAction,
			deleteAction,
			updateRouteParams,
		} = useRecordManagement({
			apiService: apiService.powerbi.workspace,
			singularName: 'workspace',
			defaultRecord: { is_active: false },
			getListFn: getList,
		});

		const {
			showModal,
			modalText,
			modalTitle,
			modalDanger,
			modalCancel,
			modalConfirm,
			modalMethod,
			showDeleteConfirmation,
		} = useModalConfirmation();

		const { isListVisible, isNoRecordVisible, isObjectCardVisible } = useResponsiveView(recordIsLoaded, record);

		// Component-specific methods
		const syncAction = async () => {
			const { success, message } = await apiService.powerbi.sync();
			if (success) {
				showToast(message);
				getList();
			} else {
				showToast(message, { type: 'error' });
			}
		};

		const showSyncConfirmation = (confirmCallback) => {
			showModal.value = true;
			modalDanger.value = false; // Sync is not a dangerous action
			modalTitle.value = 'auth.workspaces.sync_confirmation_title';
			modalText.value = 'auth.workspaces.sync_confirmation_message';
			modalCancel.value = 'generic.cancel';
			modalConfirm.value = 'generic.yes';
			modalMethod.value = confirmCallback;
		};

		const handleSyncConfirmation = () => {
			showSyncConfirmation(syncAction);
		};

		const handleMainAction = () => {
			mainAction(formGeneratorRef);
		};

		const handleDeleteConfirmation = () => {
			showDeleteConfirmation(() => deleteAction(getList));
		};

		// Manual setup of lifecycle hooks to avoid the error
		watch(
			() => route.params.id,
			() => getRecord(),
			{ immediate: true }
		);

		onMounted(() => {
			getList();
			getRecord();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});

		return {
			// From composables
			can,
			list,
			record,
			showModal,
			modalText,
			modalTitle,
			workspaces,
			modalDanger,
			modalCancel,
			modalMethod,
			modalConfirm,
			isListVisible,
			isNoRecordVisible,
			isObjectCardVisible,

			// Component-specific
			formGeneratorRef,
			syncAction,
			updateRecord,
			cancelAction,
			handleMainAction,
			handleDeleteConfirmation,
			handleSyncConfirmation,
		};
	},
});
</script>
