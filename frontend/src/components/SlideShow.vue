<template>
	<div
		ref="carouselContainer"
		class="carousel-container"
	>
		<transition :name="transition">
			<img
				v-if="images.length"
				:src="currentImage"
				class="carousel-image"
				:key="carouselKey"
			/>
		</transition>
		<button
			@click="closeCarousel"
			class="close-btn"
		>
			X
		</button>
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import { onMounted, onUnmounted } from 'vue';

export default defineComponent({
	name: 'paginaCarousel',
	props: ['broadcast', 'recarregamento', 'transition'],
	setup(props, { emit }) {
		const images = ref([]);
		const currentIndex = ref(0);
		const carouselContainer = ref(null);
		const carouselKey = ref(0);
		let timeoutId = null;
		let reloadId = null;

		const currentImage = computed(() => images.value[currentIndex.value]?.url);
		const currentDuration = computed(() => images.value[currentIndex.value]?.time || 5000);

		const getSlides = async () => {
			const { success, slides } = await apiService.signage.slides({ id: props.broadcast });
			if (success) return slides;
		};

		// Function to fetch image file
		const getFiles = async (id) => {
			try {
				const response = await apiService.repository.view({ id: id });
				const contentType =
					response.headers['content-type'] || response.headers['Content-Type'] || 'application/octet-stream';
				return URL.createObjectURL(response.data);
			} catch (error) {
				console.error('Error loading image:', error);
				return null;
			}
		};

		// Function to fetch images and sort them
		const getImages = async () => {
			const slides = await getSlides();
			const imagePromises = slides.map(async (slide) => {
				const url = await getFiles(slide.repository_id);
				return {
					id: slide.id,
					url,
					order: slide.image_order,
					time: slide.image_time,
				};
			});

			// Wait for all images to load before sorting
			const loadedImages = await Promise.all(imagePromises);
			const sortedImages = loadedImages.filter((img) => img.url !== null).sort((a, b) => a.order - b.order);

			// Check if images have changed before updating
			if (JSON.stringify(sortedImages) !== JSON.stringify(images.value)) {
				images.value = sortedImages;
				currentIndex.value = 0; // Reset index only if images changed
				startCarousel();
			} else {
				console.log('✅ Images unchanged, skipping reload.');
			}
		};

		// Function to cycle images dynamically based on `tempo`
		const startCarousel = () => {
			if (timeoutId) clearTimeout(timeoutId); // Clear previous timeout

			const cycleNext = () => {
				currentIndex.value = (currentIndex.value + 1) % images.value.length;
				carouselKey.value += 1; // Force re-render
				timeoutId = setTimeout(cycleNext, images.value[currentIndex.value]?.time * 1000 || 5000);
			};

			timeoutId = setTimeout(cycleNext, images.value[currentIndex.value]?.time * 1000 || 5000);
		};

		// Handle Esc key press
		const handleEscKey = (event) => {
			if (event.key === 'Escape' && document.fullscreenElement) {
				document.exitFullscreen(); // Exit fullscreen mode
			}
		};

		// Handle fullscreen exit
		const handleFullscreenExit = () => {
			if (!document.fullscreenElement) {
				closeCarousel(); // Close carousel when fullscreen is exited
			}
		};
		// Function to close the carousel
		const closeCarousel = () => {
			clearTimeout(timeoutId); // Stop cycling
			clearInterval(reloadId); // Stop auto-reloading
			emit('close');
		};

		// Cleanup when component is unmounted
		onUnmounted(() => {
			clearTimeout(timeoutId);
			clearInterval(reloadId);
			window.removeEventListener('keydown', handleEscKey);
			document.removeEventListener('fullscreenchange', handleFullscreenExit);
		});

		// Load images on mount
		onMounted(() => {
			getImages();
			window.addEventListener('keydown', handleEscKey);
			document.addEventListener('fullscreenchange', handleFullscreenExit);
		});

		return {
			images,
			currentImage,
			carouselKey,
			closeCarousel,
		};
	},
});
</script>

<style scoped>
.carousel-container {
	width: 100vw;
	height: 100vh;
	display: flex;
	justify-content: center;
	align-items: center;
	background-color: black;
	position: fixed;
	top: 0;
	left: 0;
}

.carousel-image {
	width: 100%;
	height: 100vh;
	object-fit: contain;
}

.close-btn {
	position: absolute;
	top: 10px;
	right: 10px;
	background: transparent;
	color: #ffffff44;
	padding: 10px;
	border: none;
	cursor: pointer;
}

/* Fade */
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.5s ease;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}

/* Slide */
.slide-enter-active,
.slide-leave-active {
	transition: transform 0.5s ease;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.slide-enter-from {
	transform: translateX(100%);
}

.slide-leave-to {
	transform: translateX(-100%);
}

/* Zoom */
.zoom-enter-active,
.zoom-leave-active {
	transition: all 0.5s ease;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.zoom-enter-from {
	opacity: 0;
	transform: scale(1.2);
}

.zoom-leave-to {
	opacity: 0;
	transform: scale(0.8);
}

/* Flip */
.flip-enter-active,
.flip-leave-active {
	transition: all 0.5s ease;
	transform-style: preserve-3d;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.flip-enter-from {
	opacity: 0;
	transform: rotateY(180deg);
}

.flip-leave-to {
	opacity: 0;
	transform: rotateY(-180deg);
}

/* Cross-fade with Scale */
.cross-enter-active,
.cross-leave-active {
	transition: all 0.5s ease;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.cross-enter-from {
	opacity: 0;
	transform: scale(1.1);
}

.cross-leave-to {
	opacity: 0;
	transform: scale(0.9);
}
</style>
