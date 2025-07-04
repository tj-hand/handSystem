<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<SelectList
			:list="list"
			:width="300"
			idField="id"
			mainContent="name"
			subContent="id"
			@addAction="addAction"
			title="auth.clients.list_title"
			:addPermission="can('add')"
			v-if="isListVisible && can('list')"
		/>
		<div class="item-data">
			<NoRecord v-if="isNoRecordVisible" />
			<ObjectCard
				:record="record"
				title_db_name="name"
				subtitle_db_name="id"
				v-if="isObjectCardVisible && can('show')"
				title_placeholder="auth.clients.title_placeholder"
				subtitle_placeholder="auth.clients.subtitle_placeholder"
			>
				<template #form>
					<PageBuilder
						:record="record"
						:schema="clients"
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
import clients from '@/pagebuilder/clients.json';
import NoRecord from '@/components/NoRecord.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import SelectList from '@/components/SelectList.vue';
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
		const { can } = usePermissions('Clients');

		const { list, getList } = useList(apiService.account.clients, 'clients');

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
			apiService: apiService.client,
			singularName: 'client',
			defaultRecord: {
				is_active: false,
				group_users: false,
				group_actions: false,
				profile_users: false,
				profile_objects: false,
				client_workspaces: false,
				user_local_actions: false,
			},
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
		const addAction = () => {
			updateRouteParams({ ...route.params, id: 'new' });
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
			clients,
			record,
			showModal,
			modalText,
			modalTitle,
			modalDanger,
			modalCancel,
			modalMethod,
			modalConfirm,
			isListVisible,
			isNoRecordVisible,
			isObjectCardVisible,

			// Component-specific
			formGeneratorRef,
			addAction,
			updateRecord,
			cancelAction,
			handleMainAction,
			handleDeleteConfirmation,
		};
	},
});
</script>
