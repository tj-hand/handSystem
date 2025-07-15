<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<PageTitle title="auth.signage.title" />
		<div class="data-container">
			<SelectList
				:list="list"
				:width="300"
				idField="id"
				mainContent="name"
				subContent="id"
				@addAction="addAction"
				title="auth.signage.list_title"
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
					title_placeholder="auth.signage.title_placeholder"
					subtitle_placeholder="auth.signage.subtitle_placeholder"
				>
					<template #form>
						<PageBuilder
							:schema="signage"
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
					<template #plus>
						<div class="slides-config">
							<div class="files">
								<FileManager
									:signageControls="true"
									permissions="Repository"
									@updateBroadcast="updateBroadcast"
								/>
							</div>
							<div class="slides">
								<Slides :key="slidesKey" />
							</div>
						</div>
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
import Slides from '@/components/Slides.vue';
import signage from '@/pagebuilder/signage.json';
import NoRecord from '@/components/NoRecord.vue';
import PageTitle from '@/components/PageTitle.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ActionsBar from '@/components/ActionsBar.vue';
import SelectList from '@/components/SelectList.vue';
import FileManager from '@/components/FileManager.vue';
import PageBuilder from '@/components/PageBuilder/index.vue';
import { formGuardService } from '@/services/formGuardService';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

// Import composables
import { useList, usePermissions, useResponsiveView, useRecordManagement, useModalConfirmation } from '@/composables';

export default defineComponent({
	name: 'Groups',
	components: {
		Slides,
		NoRecord,
		PageTitle,
		ActionsBar,
		SelectList,
		ObjectCard,
		FileManager,
		PageBuilder,
		ModalConfirmation,
	},
	setup() {
		const slidesKey = ref(0);
		const route = useRoute();
		const router = useRouter();
		const formGeneratorRef = ref(null);

		// Use composables
		const { can } = usePermissions('Signages');

		const { list, getList } = useList(apiService.client.signages, 'signages');

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
			apiService: apiService.signage,
			singularName: 'signage',
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
		const addAction = () => {
			updateRouteParams({ ...route.params, id: 'new' });
		};

		const handleMainAction = () => {
			mainAction(formGeneratorRef);
		};

		const handleDeleteConfirmation = () => {
			showDeleteConfirmation(() => deleteAction(getList));
		};

		const updateBroadcast = () => {
			slidesKey.value++;
			console.log('update');
		};

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
			signage,
			showModal,
			modalText,
			slidesKey,
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
			updateBroadcast,
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
		flex-direction: row;
		.item-data {
			flex-grow: 1;
			display: flex;
			.slides-config {
				gap: 1rem;
				flex-grow: 1;
				margin: 1rem;
				display: flex;
				flex-direction: row;
				align-items: flex-end;
				margin-bottom: 1rem * $phi-double;

				.files {
					display: flex;
					flex-grow: 1;
					min-height: 20rem;
				}
				.slides {
					flex-grow: 1;
				}
			}
		}
	}
}
</style>
