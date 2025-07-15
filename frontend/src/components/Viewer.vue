<template>
	<div
		class="file-viewer-modal"
		tabindex="-1"
		v-if="isModalVisible"
	>
		<div
			@click="handleCloseModal"
			class="modal-backdrop fade show"
		></div>

		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Header -->
				<div
					v-if="showHeader"
					class="modal-header"
				>
					<h5 class="modal-title">{{ $t('repository.fileViewer') }}</h5>
					<button
						type="button"
						class="close-button"
						@click="handleCloseModal"
						aria-label="Close"
					>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<!-- Body -->
				<div class="modal-body">
					<!-- Loading State -->
					<div
						v-if="isLoadingFile"
						class="loading-container"
					>
						<div
							class="loading-spinner"
							role="status"
						>
							<span class="visually-hidden">Loading...</span>
						</div>
						<p class="loading-text">Loading file...</p>
					</div>

					<!-- Error State -->
					<div
						v-else-if="errorMessage"
						class="error-container"
					>
						<div class="error-icon">⚠️</div>
						<p class="error-message">{{ errorMessage }}</p>
						<button
							@click="handleRetryLoad"
							class="retry-button"
						>
							Try Again
						</button>
					</div>

					<!-- File Preview -->
					<template v-else-if="fileDataUrl">
						<!-- Image Preview -->
						<div
							v-if="isImageFile"
							class="image-preview-container"
						>
							<img
								:src="fileDataUrl"
								class="preview-image"
								alt="File preview"
								@load="handleImageLoad"
								@error="handleImageError"
							/>
						</div>

						<!-- PDF Preview -->
						<div
							v-else-if="isPdfFile"
							class="pdf-preview-container"
						>
							<embed
								:src="fileDataUrl"
								type="application/pdf"
								class="pdf-embed"
							/>
						</div>

						<!-- Video Preview -->
						<div
							v-else-if="isVideoFile"
							class="video-preview-container"
						>
							<video
								controls
								class="video-player"
								preload="metadata"
							>
								<source
									:src="fileDataUrl"
									:type="fileMimeType"
								/>
								Your browser does not support the video tag.
							</video>
						</div>

						<!-- Audio Preview -->
						<div
							v-else-if="isAudioFile"
							class="audio-preview-container"
						>
							<audio
								controls
								class="audio-player"
								preload="metadata"
							>
								<source
									:src="fileDataUrl"
									:type="fileMimeType"
								/>
								Your browser does not support the audio tag.
							</audio>
						</div>

						<!-- Unsupported File Type -->
						<div
							v-else
							class="unsupported-file-container"
						>
							<div class="unsupported-icon">📄</div>
							<p class="unsupported-message">This file type cannot be previewed</p>
							<a
								:href="fileDataUrl"
								download
								class="download-button"
							>
								Download File
							</a>
						</div>
					</template>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { onUnmounted } from 'vue';
import { defineComponent } from 'vue';
import { ref, computed, watch } from 'vue';
import apiService from '@/api/apiService';

export default defineComponent({
	name: 'FileViewerModal',

	props: {
		isVisible: { type: Boolean, default: false },
		showHeader: { type: Boolean, default: false },
		fileId: { type: [String, null], default: null },
	},

	emits: ['close'],

	setup(props, { emit }) {
		// Reactive state
		const isLoadingFile = ref(false);
		const errorMessage = ref(null);
		const fileDataUrl = ref(null);
		const fileMimeType = ref(null);

		// Computed properties
		const isModalVisible = computed(() => props.isVisible);
		const isImageFile = computed(() => fileMimeType.value?.startsWith('image/'));
		const isPdfFile = computed(() => fileMimeType.value === 'application/pdf');
		const isVideoFile = computed(() => fileMimeType.value?.startsWith('video/'));
		const isAudioFile = computed(() => fileMimeType.value?.startsWith('audio/'));

		// Methods
		const loadFileData = async () => {
			if (!props.fileId) {
				errorMessage.value = 'No file ID provided';
				return;
			}

			isLoadingFile.value = true;
			errorMessage.value = null;

			try {
				const response = await apiService.repository.view({ id: props.fileId });

				const contentType =
					response.headers['content-type'] || response.headers['Content-Type'] || 'application/octet-stream';
				fileMimeType.value = contentType;
				fileDataUrl.value = URL.createObjectURL(response.data);
			} catch (error) {
				console.error('Error loading file:', error);

				if (error.code === 'ECONNABORTED') {
					errorMessage.value = 'Request timeout. The file might be too large.';
				} else if (error.response?.status === 404) {
					errorMessage.value = 'File not found.';
				} else if (error.response?.status === 403) {
					errorMessage.value = 'Access denied to this file.';
				} else {
					errorMessage.value = 'Error loading file. Please try again.';
				}
			} finally {
				isLoadingFile.value = false;
			}
		};

		const cleanupFileUrl = () => {
			if (fileDataUrl.value) {
				URL.revokeObjectURL(fileDataUrl.value);
				fileDataUrl.value = null;
			}
		};

		const resetModalState = () => {
			cleanupFileUrl();
			errorMessage.value = null;
			fileMimeType.value = null;
		};

		const handleCloseModal = () => {
			emit('close');
			resetModalState();
		};

		const handleRetryLoad = () => {
			resetModalState();
			loadFileData();
		};

		const handleImageLoad = () => {};

		const handleImageError = () => {
			errorMessage.value = 'Failed to load image preview';
		};

		// Watchers
		watch(
			() => props.isVisible,
			(newValue) => {
				if (newValue && props.fileId) {
					loadFileData();
				} else if (!newValue) {
					resetModalState();
				}
			}
		);

		watch(
			() => props.fileId,
			(newFileId, oldFileId) => {
				if (newFileId !== oldFileId && props.isVisible) {
					resetModalState();
					if (newFileId) {
						loadFileData();
					}
				}
			}
		);

		// Lifecycle hooks
		onUnmounted(() => {
			cleanupFileUrl();
		});

		// Return everything that needs to be available in the template
		return {
			// State
			isLoadingFile,
			errorMessage,
			fileDataUrl,
			fileMimeType,

			// Computed
			isModalVisible,
			isImageFile,
			isPdfFile,
			isVideoFile,
			isAudioFile,

			// Methods
			handleCloseModal,
			handleRetryLoad,
			handleImageLoad,
			handleImageError,
		};
	},
});
</script>

<style lang="scss" scoped>
.file-viewer-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 1050;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 1rem;

	.modal-backdrop {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 1040;
		transition: opacity 0.15s linear;

		&.fade.show {
			opacity: 1;
		}
	}

	.modal-dialog {
		position: relative;
		width: 100%;
		max-width: 90vw;
		max-height: 90vh;
		z-index: 1055;

		.modal-content {
			position: relative;
			display: flex;
			flex-direction: column;
			width: 100%;
			max-height: 90vh;
			background-color: #fff;
			border: 1px solid rgba(0, 0, 0, 0.2);
			border-radius: 0.375rem;
			box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
			outline: 0;

			.modal-header {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 1rem 1rem 0.5rem;
				border-bottom: 1px solid #dee2e6;

				.modal-title {
					margin: 0;
					font-size: 1.1rem;
					font-weight: 500;
					color: #495057;
				}

				.close-button {
					background: none;
					border: none;
					font-size: 1.5rem;
					font-weight: 700;
					line-height: 1;
					color: #000;
					opacity: 0.5;
					cursor: pointer;
					padding: 0;
					margin: 0;

					&:hover,
					&:focus {
						opacity: 0.75;
						outline: none;
					}
				}
			}

			.modal-body {
				position: relative;
				flex: 1 1 auto;
				padding: 1rem;
				overflow: auto;

				// Loading State
				.loading-container {
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					padding: 3rem 1rem;
					text-align: center;

					.loading-spinner {
						width: 3rem;
						height: 3rem;
						border: 0.25rem solid #f3f3f3;
						border-top: 0.25rem solid #007bff;
						border-radius: 50%;
						animation: spin 1s linear infinite;
						margin-bottom: 1rem;
					}

					.loading-text {
						color: #6c757d;
						margin: 0;
						font-size: 0.9rem;
					}
				}

				// Error State
				.error-container {
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					padding: 2rem 1rem;
					text-align: center;

					.error-icon {
						font-size: 3rem;
						margin-bottom: 1rem;
					}

					.error-message {
						color: #dc3545;
						margin-bottom: 1.5rem;
						font-size: 1rem;
					}

					.retry-button {
						background-color: #007bff;
						color: white;
						border: none;
						padding: 0.5rem 1rem;
						border-radius: 0.25rem;
						cursor: pointer;
						font-size: 0.9rem;
						transition: background-color 0.15s ease-in-out;

						&:hover {
							background-color: #0056b3;
						}

						&:focus {
							outline: none;
							box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
						}
					}
				}

				// Preview Containers
				.image-preview-container,
				.pdf-preview-container,
				.video-preview-container,
				.audio-preview-container {
					display: flex;
					justify-content: center;
					align-items: center;

					.preview-image {
						max-width: 100%;
						max-height: 70vh;
						height: auto;
						border-radius: 0.25rem;
						box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
					}

					.pdf-embed {
						width: 100%;
						height: 70vh;
						border: none;
						border-radius: 0.25rem;
					}

					.video-player,
					.audio-player {
						width: 100%;
						max-width: 100%;
						border-radius: 0.25rem;
					}

					.video-player {
						max-height: 70vh;
					}
				}

				.unsupported-file-container {
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					padding: 3rem 1rem;
					text-align: center;

					.unsupported-icon {
						font-size: 4rem;
						margin-bottom: 1rem;
						opacity: 0.5;
					}

					.unsupported-message {
						color: #6c757d;
						margin-bottom: 1.5rem;
						font-size: 1rem;
					}

					.download-button {
						display: inline-block;
						background-color: #007bff;
						color: white;
						text-decoration: none;
						padding: 0.75rem 1.5rem;
						border-radius: 0.25rem;
						font-weight: 500;
						transition: background-color 0.15s ease-in-out;

						&:hover {
							background-color: #0056b3;
							text-decoration: none;
							color: white;
						}

						&:focus {
							outline: none;
							box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
						}
					}
				}
			}
		}
	}
}

// Animations
@keyframes spin {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}

// Responsive Design
@media (max-width: 768px) {
	.file-viewer-modal {
		padding: 0.5rem;

		.modal-dialog {
			max-width: 95vw;
			max-height: 95vh;

			.modal-content {
				max-height: 95vh;

				.modal-body {
					padding: 0.75rem;

					.pdf-embed,
					.video-player {
						height: 60vh;
						max-height: 60vh;
					}

					.preview-image {
						max-height: 60vh;
					}
				}
			}
		}
	}
}

// Accessibility improvements
@media (prefers-reduced-motion: reduce) {
	.loading-spinner {
		animation: none;
	}

	.modal-backdrop {
		transition: none;
	}

	.retry-button,
	.download-button {
		transition: none;
	}
}
</style>
