<template>
	<div class="digital-signage-programming">
		<div class="header-section">
			<div class="section-title">
				{{ $t('auth.signage.selected-files') }}
			</div>
			<div class="controls-section">
				<div class="transition-selector">
					<label>{{ $t('generic.transicions') }}</label>
					<select
						v-model="selectedTransition"
						class="transition-dropdown"
					>
						<option
							v-for="transition in availableTransitions"
							:key="transition.value"
							:value="transition.value"
						>
							{{ transition.label }}
						</option>
					</select>
				</div>
				<div
					v-if="slideList.length > 0"
					class="play-button"
					@click="startSlideshow"
					:title="$t('auth.signage.test-slideshow')"
				>
					<span class="icon">smart_display</span>
				</div>
			</div>
		</div>

		<div class="slides-container">
			<ul class="slides-list">
				<li
					v-if="slideList.length === 0"
					class="empty-state"
				>
					{{ $t('auth.repository.no-files') }}
				</li>
				<li
					v-else
					v-for="(slide, index) in slideList"
					:key="slide.id"
					class="slide-item"
				>
					<div class="slide-info">
						<div class="slide-name">
							{{ slide.display_name }}
						</div>
					</div>
					<div class="slide-actions">
						<div class="time-input-wrapper">
							<input
								class="time-input"
								type="number"
								min="1"
								max="999"
								v-model.number="slide.image_time"
								@blur="updateSlideTime(slide)"
								@keyup.enter="updateSlideTime(slide)"
								:title="$t('auth.signage.slide-time')"
							/>
							<span class="time-unit">s</span>
						</div>
						<div class="action-icon timer-icon">
							<span class="icon">timer</span>
						</div>
						<div
							class="action-icon move-up-icon"
							@click="moveSlideUp(slide.id)"
							:title="$t('auth.signage.move-up')"
							:class="{ disabled: index === 0 }"
						>
							<span class="icon">arrow_upward</span>
						</div>
						<div
							class="action-icon move-down-icon"
							@click="moveSlideDown(slide.id)"
							:title="$t('auth.signage.move-down')"
							:class="{ disabled: index === slideList.length - 1 }"
						>
							<span class="icon">arrow_downward</span>
						</div>
						<div
							class="action-icon delete-icon"
							@click="confirmSlideDelete(slide.id)"
							:title="$t('generic.delete')"
						>
							<span class="icon">delete</span>
						</div>
					</div>
				</li>
			</ul>
		</div>

		<SlideShow
			v-if="startCarousel"
			@close="closeCarousel"
			:broadcast="broadcastId"
		/>

		<ModalConfirmation
			:isDanger="true"
			v-model="showDeleteModal"
			@confirm="deleteSelectedSlide"
			:cancelButton="$t('generic.cancel')"
			:confirmButton="$t('generic.delete')"
			:title="$t('auth.signage.modal.destroy_title')"
			:message="$t('auth.signage.modal.destroy_text')"
		/>
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { defineComponent } from 'vue';

// Import API service
import apiService from '@/api/apiService';

// Import components
import SlideShow from '@/components/SlideShow.vue';
import ModalConfirmation from '@/components/ModalConfirmation.vue';

export default defineComponent({
	name: 'DigitalSignageProgramming',
	components: {
		SlideShow,
		ModalConfirmation,
	},
	emits: ['slideshow-started', 'slides-updated', 'slide-deleted', 'slide-moved', 'slide-time-updated'],
	setup(props, { emit }) {
		// Reactive data
		const slideList = ref([]);
		const isLoading = ref(false);
		const slideToDelete = ref(null);
		const startCarousel = ref(false);
		const showDeleteModal = ref(false);
		const selectedTransition = ref('fade');

		// Route data
		const route = useRoute();
		const broadcastId = ref(route.params.id);

		// Transition options
		const availableTransitions = ref([
			{ value: 'fade', label: 'Fade' },
			{ value: 'slide', label: 'Slide' },
			{ value: 'flip', label: 'Flip' },
			{ value: 'cross', label: 'Cross' },
			{ value: 'zoom', label: 'Zoom' },
			{ value: 'blur', label: 'Blur' },
		]);

		// Computed properties
		const hasSlides = computed(() => slideList.value.length > 0);
		const canPlaySlideshow = computed(() => hasSlides.value && !isLoading.value);

		// API Methods
		const fetchSlides = async () => {
			try {
				isLoading.value = true;
				const { success, slides } = await apiService.signage.slides({ id: route.params.id });
				if (success) slideList.value = slides || [];
			} catch (error) {
				console.error('Error fetching slides:', error);
			} finally {
				isLoading.value = false;
			}
		};

		const moveSlideUp = async (slideId) => {
			const { success } = await apiService.signage.moveSlideUp({ id: slideId });
			if (success) await fetchSlides();
		};

		const moveSlideDown = async (slideId) => {
			const { success } = await apiService.signage.moveSlideDown({ id: slideId });
			if (success) await fetchSlides();
		};

		const deleteSlide = async (slideId) => {
			const { success } = await apiService.signage.deleteSlide({ id: slideId });
			if (success) await fetchSlides();
		};

		const updateSlideTime = async (slide) => {
			if (!slide.image_time || slide.image_time < 1) slide.image_time = 5;
			await apiService.signage.setSlideTime({
				id: slide.id,
				time: slide.image_time,
			});
		};

		// Event handlers
		const startSlideshow = () => {
			startCarousel.value = true;
		};

		const closeCarousel = () => {
			startCarousel.value = false;
		};

		const confirmSlideDelete = (slideId) => {
			slideToDelete.value = slideId;
			showDeleteModal.value = true;
		};

		const deleteSelectedSlide = async () => {
			if (slideToDelete.value) {
				await deleteSlide(slideToDelete.value);
				slideToDelete.value = null;
			}
			showDeleteModal.value = false;
		};

		// Lifecycle
		onMounted(() => {
			fetchSlides();
		});

		return {
			// Data
			slideList,
			isLoading,
			broadcastId,
			startCarousel,
			showDeleteModal,
			selectedTransition,
			availableTransitions,

			// Computed
			hasSlides,
			canPlaySlideshow,

			// Methods
			fetchSlides,
			moveSlideUp,
			moveSlideDown,
			closeCarousel,
			startSlideshow,
			updateSlideTime,
			confirmSlideDelete,
			deleteSelectedSlide,
		};
	},
});
</script>

<style lang="scss" scoped>
.digital-signage-programming {
	height: 100%;
	display: flex;
	min-height: 15rem;
	flex-direction: column;

	.header-section {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
		padding: 0 1rem;

		.section-title {
			color: $text-color;
			font-weight: bold;
			font-size: 1.1rem;
		}

		.controls-section {
			display: flex;
			align-items: center;
			gap: 1rem;

			.transition-selector {
				display: flex;
				align-items: center;
				gap: 0.5rem;

				label {
					color: $text-color;
					font-size: 0.9rem;
					font-weight: 500;
				}

				.transition-dropdown {
					padding: 0.5rem 1rem;
					border: 1px solid rgba(0, 0, 0, 0.1);
					border-radius: 4px;
					background-color: white;
					color: $text-color;
					font-size: 0.875rem;
					cursor: pointer;
					transition: border-color 0.2s ease;

					&:focus {
						outline: none;
						border-color: $primary-color;
						box-shadow: 0 0 0 2px rgba($primary-color, 0.2);
					}

					&:hover {
						border-color: rgba(0, 0, 0, 0.2);
					}
				}
			}

			.play-button {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 2.5rem;
				height: 2.5rem;
				background-color: $success-color;
				border-radius: 50%;
				cursor: pointer;
				transition: all 0.2s ease;

				&:hover {
					background-color: rgba(0, 0, 0, 0.1);
					transform: scale(1.05);
				}

				.icon {
					color: white;
					font-size: 1.5rem;
				}
			}
		}
	}

	.slides-container {
		flex-grow: 1;
		height: 16em;
		overflow-y: auto;
		border: 1px solid rgba(0, 0, 0, 0.1);
		border-radius: 4px;

		.slides-list {
			margin: 0;
			padding: 0;
			list-style: none;

			.empty-state {
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 3rem;
				color: $text-color;
				font-style: italic;
				opacity: 0.7;
			}

			.slide-item {
				display: flex;
				align-items: center;
				padding: 1rem * $phi;
				justify-content: space-between;
				transition: background-color 0.2s ease;
				background-color: rgba(255, 255, 255, 0.9);
				border-bottom: 1px solid rgba(0, 0, 0, 0.05);

				&:hover {
					background-color: rgba(0, 0, 0, 0.02);
				}

				&:last-child {
					border-bottom: none;
				}

				.slide-info {
					flex-grow: 1;

					.slide-name {
						color: $text-color;
						font-weight: 500;
						cursor: default;
					}
				}

				.slide-actions {
					display: flex;
					align-items: center;
					gap: 1rem;

					.time-input-wrapper {
						display: flex;
						align-items: center;
						gap: 0.25rem;
						padding: 0.25rem 0.5rem;
						border: 1px solid rgba(0, 0, 0, 0.1);
						border-radius: 4px;
						background-color: white;

						.time-input {
							width: 3rem;
							border: none;
							text-align: center;
							font-size: 0.875rem;
							color: $text-color;

							&:focus {
								outline: none;
							}

							&::-webkit-outer-spin-button,
							&::-webkit-inner-spin-button {
								-webkit-appearance: none;
								margin: 0;
							}

							&[type='number'] {
								-moz-appearance: textfield;
							}
						}

						.time-unit {
							font-size: 0.75rem;
							color: $text-color;
							opacity: 0.7;
						}
					}

					.action-icon {
						display: flex;
						align-items: center;
						justify-content: center;
						width: 2rem;
						height: 2rem;
						border-radius: 4px;
						cursor: pointer;
						transition: all 0.2s ease;

						&.disabled {
							opacity: 0.3;
							cursor: not-allowed;
							pointer-events: none;
						}

						.icon {
							font-size: 1.2rem;
						}

						&.timer-icon {
							color: $text-color;
							cursor: default;
						}

						&.move-up-icon,
						&.move-down-icon {
							color: $success-color;

							&:hover {
								background-color: rgba($success-color, 0.1);
								transform: translateY(-1px);
							}
						}

						&.delete-icon {
							color: $warning-color;

							&:hover {
								background-color: rgba($warning-color, 0.1);
								transform: scale(1.05);
							}
						}
					}
				}
			}
		}
	}
}
</style>
