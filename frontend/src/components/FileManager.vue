<template>
	<div class="file-manager">
		<div
			v-if="can('upload')"
			class="button-wrapper"
		>
			<label
				for="file-upload"
				class="button primary"
			>
				<div class="icon">upload</div>
				<div>{{ $t('auth.repository.add_files') }}</div>
			</label>
			<input
				multiple
				type="file"
				class="no-show"
				id="file-upload"
				@change="uploadFiles"
			/>
		</div>
		<div
			v-if="can('list')"
			class="files-list"
		>
			<div
				class="no-files"
				v-if="files.length === 0"
			>
				<span class="icon">folder_open</span><br />
				{{ $t('auth.repository.no-files') }}
			</div>
			<div
				v-else
				class="list-group"
			>
				<div
					:key="index"
					class="item"
					v-for="(file, index) in files"
					:class="{ dinamic: file.file_type === 'dinamic' }"
				>
					<div class="file">
						<div
							class="name"
							v-if="editingIndex !== index"
							@click="handleClick(file)"
							@dblclick="handleDoubleClick(index, file)"
						>
							{{ file.display_name }}
						</div>
						<div
							v-else
							class="page-builder"
						>
							<div class="form-element-row">
								<div class="form-element-wrapper">
									<input
										class="input-text"
										v-if="can('rename')"
										v-model="newFileName"
										@blur="saveFileName(file.id, index)"
										:ref="(el) => (inputFields[index] = el)"
										@keyup.enter="saveFileName(file.id, index)"
									/>
								</div>
							</div>
						</div>
					</div>
					<div class="actions">
						<div
							v-if="can('view')"
							class="action view"
							@click="viewFile(file)"
							:title="$t('generic.view')"
						>
							<span class="icon">preview</span>
						</div>
						<div
							class="action rename"
							v-if="file.file_type === 'static' && can('rename')"
							@click="renameFile(index, file.id, file.display_name)"
							:title="$t('generic.rename')"
						>
							<span class="icon">published_with_changes</span>
						</div>
						<div
							class="action delete"
							:title="$t('generic.delete')"
							v-if="file.file_type === 'static' && can('delete')"
							@click="handleDeleteConfirmation(file.id)"
						>
							<span class="icon">delete</span>
						</div>
						<div
							v-if="signageControls"
							@click="addToBroadcast(file.id)"
							class="action add-to-broadcast"
							:title="$t('auth.signage.add-to-broadcast')"
						>
							<span class="icon">playlist_play</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<Viewer
		v-if="can('view')"
		@close="closeViewer"
		:isVisible="showViewer"
		:fileId="selectedFileID"
	/>

	<ModalConfirmation
		:isDanger="true"
		v-model="showModal"
		@confirm="destroyFile"
		:cancelButton="$t('generic.no')"
		:confirmButton="$t('generic.yes')"
		:title="$t('auth.repository.modal.destroy_title')"
		:message="$t('auth.repository.modal.destroy_text')"
	/>
</template>

<script>
// Import from Vue
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { defineComponent } from 'vue';
import { onMounted, nextTick } from 'vue';

// Import Components and Services
import apiService from '@/api/apiService';
import Viewer from '@/components/Viewer.vue';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

// Import composables
import { usePermissions } from '@/composables';

export default defineComponent({
	name: 'FileManager',
	components: {
		Viewer,
		ModalConfirmation,
	},
	props: {
		permissions: { type: String, required: true },
		signageControls: { type: Boolean, default: false },
	},
	emits: ['updateBroadcast'],
	setup(props, { emit }) {
		const files = ref([]);
		const route = useRoute();
		const newFileName = ref('');
		const inputFields = ref([]);
		const showModal = ref(false);
		const showViewer = ref(false);
		const editingIndex = ref(null);
		const clickTimeout = ref(null);
		const selectedFileID = ref(null);
		const { can } = usePermissions(props.permissions);

		const fetchFiles = async () => {
			const { success, filesIndex } = await apiService.client.files();
			if (success) files.value = filesIndex;
		};

		const uploadFiles = async (event) => {
			const uploadedFiles = event.target.files;
			for (let file of uploadedFiles) {
				const params = new FormData();
				params.append('files[]', file);
				const { success } = await apiService.repository.upload(params);
				fetchFiles();
			}
		};

		function handleClick(file) {
			if (clickTimeout.value) {
				clearTimeout(clickTimeout.value);
				clickTimeout.value = null;
			}

			clickTimeout.value = setTimeout(() => {
				if (can('view')) viewFile(file);
				clickTimeout.value = null;
			}, 250);
		}

		function handleDoubleClick(index, file) {
			if (clickTimeout.value) {
				clearTimeout(clickTimeout.value);
				clickTimeout.value = null;
			}
			if (file.file_type === 'static' && can('rename')) renameFile(index, file.id, file.display_name);
		}

		const renameFile = async (index, id, name) => {
			selectedFileID.value = id;
			editingIndex.value = index;
			newFileName.value = name;
			await nextTick();
			if (inputFields.value[index]) {
				inputFields.value[index].focus();
				inputFields.value[index].select();
			}
		};

		const handleDeleteConfirmation = (index) => {
			selectedFileID.value = index;
			showModal.value = true;
		};

		const destroyFile = async () => {
			await apiService.repository.destroy({ id: selectedFileID.value });
			fetchFiles();
			showModal.value = false;
		};

		const saveFileName = async (id, index) => {
			if (!newFileName.value.trim() || newFileName.value === files.value[index].display_name) {
				editingIndex.value = null;
				return;
			}
			await apiService.repository.rename({ id: selectedFileID.value, name: newFileName.value });
			files.value[index].display_name = newFileName.value;
			editingIndex.value = null;
			fetchFiles();
		};

		const viewFile = (file) => {
			selectedFileID.value = file.id;
			showViewer.value = true;
		};

		const closeViewer = () => {
			showViewer.value = false;
			selectedFileID.value = null;
		};

		const addToBroadcast = async (id) => {
			const { success } = await apiService.signage.addToBroadcast({
				slide_id: id,
				signage_id: route.params.id,
			});
			if (success) emit('updateBroadcast');
		};

		onMounted(() => {
			fetchFiles();
		});

		return {
			can,
			files,
			showModal,
			showViewer,
			newFileName,
			inputFields,
			editingIndex,
			selectedFileID,
			viewFile,
			renameFile,
			uploadFiles,
			handleClick,
			closeViewer,
			destroyFile,
			saveFileName,
			addToBroadcast,
			handleDoubleClick,
			handleDeleteConfirmation,
		};
	},
});
</script>

<style lang="scss" scoped>
.file-manager {
	flex-grow: 1;
	display: flex;
	min-height: 0;
	flex-direction: column;
	.button-wrapper {
		width: 100%;
		display: flex;
		justify-content: flex-end;
		.no-show {
			display: none;
		}
	}
	.files-list {
		flex-grow: 1;
		display: flex;
		margin-top: 1rem;
		overflow-y: auto;
		border: 1px solid rgba(0, 0, 0, 0.1);

		.no-files {
			width: 100%;
			font-weight: 100;
			text-align: center;
			color: $text-color;
			align-self: center;
			font-size: 1rem * $phi-double;
		}

		.list-group {
			flex-grow: 1;
			display: flex;
			flex-direction: column;

			.item {
				display: flex;
				padding-left: 1rem;
				flex-direction: row;
				align-items: center;
				justify-content: space-between;
				background-color: rgba(255, 255, 255, 0.9);
				border-bottom: 1px solid rgba(0, 0, 0, 0.1);

				&.dinamic {
					background-color: rgba($primary-color, 0.05);
				}
			}

			.file {
				flex-grow: 1;
				display: flex;
				color: $text-color;
				flex-direction: row;

				.name {
					cursor: pointer;
					padding: 1rem * $phi;
				}
			}

			.actions {
				gap: 1rem;
				flex-grow: 0;
				display: flex;
				padding: 0 2rem;
				justify-content: flex-end;

				.action {
					cursor: pointer;
					font-size: 1rem * $phi-up;

					&.view {
						color: $primary-color;
					}

					&.rename {
						color: $success-color;
					}

					&.delete {
						color: $warning-color;
					}

					&.add-to-broadcast {
						color: $secondary-color;
					}
				}
			}
		}
	}
}
</style>
