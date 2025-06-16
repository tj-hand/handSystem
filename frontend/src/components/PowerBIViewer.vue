<template>
	<div
		class="power-bi"
		v-if="can"
	>
		<div
			v-show="isLoaded"
			:class="{ fullScreen: isFullscreen }"
		>
			<div
				v-if="isFullscreen"
				class="button-wrapper"
				@click="exitFullScreen"
			>
				<div class="fullscreen-button button neutral">
					<span class="icon">fullscreen_exit</span>{{ $t('generic.full_screen_exit') }}
				</div>
			</div>
			<div
				v-else
				class="button-wrapper"
			>
				<div
					class="bookmark"
					@click="toggleBookmark"
				>
					<div
						v-if="bookmark"
						class="is_active"
					>
						<div class="icon">beenhere</div>
						<div class="text">{{ $t('generic.favorite') }}</div>
					</div>
					<div v-else>
						<div class="icon">bookmark</div>
						<div class="text">{{ $t('generic.to_favorite') }}</div>
					</div>
				</div>
				<div
					@click="fullScreen"
					class="fullscreen-button button neutral"
				>
					<span class="icon">fullscreen</span>{{ $t('generic.full_screen_view') }}
				</div>
			</div>
			<div
				id="reportContainer"
				class="reporterContainer"
			></div>
		</div>
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { ref, computed } from 'vue';
import * as pbi from 'powerbi-client';
import { defineComponent } from 'vue';
import { useRoute } from 'vue-router';
import { isUUID } from '@/tools/isUUID';
import apiService from '@/api/apiService';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'PowerBIViewer',
	setup(props) {
		const route = useRoute();
		const isLoaded = ref(false);
		const bookmark = ref(false);
		const authStore = useAuthStore();

		const isFullscreen = ref(false);

		const can = computed(() => {
			return authStore.enviroment?.permissions['BIs']?.some((p) => p.identifier === 'auth.bis.powerbi');
		});

		const fullScreen = () => {
			isFullscreen.value = true;
		};

		const exitFullScreen = () => {
			isFullscreen.value = false;
		};

		const getDashboard = async () => {
			if (isUUID(route.params.id)) {
				const { success, message, params } = await apiService.powerbi.bis.render({ id: route.params.id });
				if (success) {
					isLoaded.value = true;
					bookmark.value = params.bookmark;
					render(params);
				}
			}
		};

		const toggleBookmark = async () => {
			const { success } = await apiService.powerbi.bis.bookmark({ id: route.params.id });
			if (success) bookmark.value = !bookmark.value;
		};

		const render = (values) => {
			const embedReport = () => {
				const permissions = pbi.models.Permissions.All;

				const config = {
					type: values.type,
					tokenType: pbi.models.TokenType.Embed,
					accessToken: values.token,
					embedUrl: values.url,
					id: values.dashboard.microsoft_id,
					permissions: permissions,
				};

				let powerbi = new pbi.service.Service(
					pbi.factories.hpmFactory,
					pbi.factories.wpmpFactory,
					pbi.factories.routerFactory
				);

				const dashboardContainer = document.getElementById('reportContainer');
				if (dashboardContainer !== null) powerbi.embed(dashboardContainer, config);
			};

			embedReport();
		};

		onMounted(() => {
			getDashboard();
		});

		return {
			can,
			isLoaded,
			bookmark,
			isFullscreen,
			fullScreen,
			exitFullScreen,
			toggleBookmark,
		};
	},
});
</script>

<style lang="scss" scoped>
.power-bi {
	width: 100%;
	height: 60vh;
	padding: 0 (1rem * $phi-up);

	.button-wrapper {
		width: 100%;
		display: flex;
		color: $text-color;
		flex-direction: row;
		align-items: center;
		padding: 1rem * $phi-down;
		justify-content: space-between;

		.bookmark {
			cursor: pointer;
			text-align: center;
			$color: $text-color;
			font-size: 1rem * $phi-up;

			.is_active {
				color: $success-color;
			}
			.text {
				font-size: 1rem * $phi;
			}
		}

		.fullscreen-button {
			flex-grow: 0;
			display: flex;
			padding-top: 1rem * $phi-down;
			padding-bottom: 1rem * $phi-down;
		}
	}

	.fullScreen {
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 9999;
		position: fixed;
		background: $background-color;

		.button-wrapper {
			justify-content: flex-end;
			.fullscreen-button {
				margin-right: 1rem * $phi-double;
			}
		}

		.reporterContainer {
			height: 90vh;
		}
	}

	.reporterContainer {
		width: 100%;
		height: 60vh;
	}
}

@media (max-width: 767px) {
	.power-bi {
		padding: 0;
		height: 50vh;
		margin-top: 1rem;

		.button-wrapper {
			max-width: 100%;
		}

		.fullScreen {
			overflow-x: auto;

			.fullscreen-button {
				position: fixed;
				margin-top: 2rem;
			}

			.reporterContainer {
				height: 85vh;
				margin-top: 2rem;
				min-width: 1024px;
			}
		}
	}
}
</style>
