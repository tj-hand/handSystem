<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<PageTitle title="auth.bis.title" />
		<div class="data-container">
			<SelectList
				:list="list"
				:width="300"
				idField="id"
				mainContent="title"
				subContent="id"
				@addAction="addAction"
				title="auth.bis.list_title"
				:addPermission="can('add')"
				v-if="isListVisible && can('list')"
			/>
			<div class="item-data">
				<NoRecord v-if="isNoRecordVisible" />
				<ObjectCard
					:record="record"
					title_db_name="local_name"
					subtitle_db_name="id"
					v-if="isObjectCardVisible && can('show')"
				>
					<template #form>
						<PageBuilder
							:schema="bis"
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
				<PowerBIViewer
					v-if="!can('show') && can('powerbi') && !isNoRecordVisible"
					class="only-powerbi"
				/>
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
// Import from Vue
import { defineComponent } from 'vue';
import { ref, computed, watch } from 'vue';
import { onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// Import Components and Services
import bis from '@/pagebuilder/bis.json';
import apiService from '@/api/apiService';
import NoRecord from '@/components/NoRecord.vue';
import PageTitle from '@/components/PageTitle.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import SelectList from '@/components/SelectList.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import PowerBIViewer from '@/components/PowerBIViewer.vue';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

// Import composables
import { useList, usePermissions, useResponsiveView, useRecordManagement, useModalConfirmation } from '@/composables';

export default defineComponent({
	name: 'BIs',
	components: {
		NoRecord,
		PageTitle,
		ActionsBar,
		SelectList,
		ObjectCard,
		PageBuilder,
		PowerBIViewer,
		ModalConfirmation,
	},
	setup() {
		const route = useRoute();
		const router = useRouter();
		const authStore = useAuthStore();
		const formGeneratorRef = ref(null);

		const powerbi = computed(() => {
			return authStore.enviroment?.permissions['BIs']?.some((p) => p.identifier === 'auth.bis.powerbi');
		});

		// Use composables
		const { can } = usePermissions('BIs');

		const { list, getList } = useList(apiService.powerbi.bis.list, 'pbi_objects');

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
			apiService: apiService.powerbi.bis,
			singularName: 'pbi_object',
			defaultRecord: { is_active: false, scope: 'client' },
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
			() => getRecord(powerbi.value),
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
			bis,
			can,
			list,
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

<style lang="scss" scoped>
.content-wrapper {
	.data-container {
		flex-grow: 1;
		display: flex;
		min-height: 0;
		flex-direction: row;
		.item-data {
			flex-grow: 1;
			display: flex;
			.only-powerbi {
				margin-top: 1rem * $phi-up;
			}
		}
	}
}
</style>
