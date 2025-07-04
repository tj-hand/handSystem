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
			title="auth.groups.list_title"
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
				title_placeholder="auth.groups.title_placeholder"
				subtitle_placeholder="auth.groups.subtitle_placeholder"
			>
				<template #form>
					<PageBuilder
						:schema="groups"
						:record="record"
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
import groups from '@/pagebuilder/groups.json';
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
		const { can } = usePermissions('Groups');

		const { list, getList } = useList(apiService.account.groups, 'groups');

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
			apiService: apiService.group,
			singularName: 'group',
			defaultRecord: { group_type: 'permissions_group', is_active: false, scope: 'account' },
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
			groups,
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
