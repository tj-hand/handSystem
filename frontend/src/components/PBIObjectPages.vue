<template>
	<div class="pbi-object-pages">
		<div class="title">{{ $t('auth.bis.list_pages') }}</div>
		<div class="list">
			<ul class="group">
				<li
					:key="index"
					class="page"
					v-if="pages.length > 0"
					v-for="(page, index) in pages"
					:class="{ active: selectedItem === index }"
				>
					<div
						@click="selectPage(index, page)"
						class="page-name"
					>
						<div
							class="icon"
							:class="{ hasPhoto: page.hasPhoto }"
						>
							photo_camera
						</div>
						<div>{{ page.displayName }}</div>
					</div>
					<div
						v-if="page.hasPhoto && page.status == 'updating'"
						class="icon pending"
					>
						update
					</div>
					<div
						v-if="page.hasPhoto && page.status == 'pending'"
						class="icon pending"
					>
						schedule
					</div>
					<div
						v-if="page.hasPhoto && page.status == 'success'"
						class="icon delete"
						@click="destroyImage(page)"
					>
						delete
					</div>
				</li>
				<li v-else>
					<div class="page-name empty">{{ $t('auth.bis.without_pages') }}</div>
				</li>
			</ul>
		</div>
		<div class="page-builder">
			<div class="name">
				<label class="label">{{ $t('auth.bis.label.file_name') }}</label>
				<div class="form-element-row">
					<div class="form-element-wrapper">
						<input
							class="input-text"
							type="text"
							v-model="name"
						/>
					</div>
				</div>
			</div>
			<div class="time">
				<label class="label">{{ $t('auth.bis.label.update_time') }}</label>
				<div class="form-element-row">
					<div class="form-element-wrapper">
						<input
							class="input-text"
							type="text"
							v-model="time"
						/>
					</div>
				</div>
			</div>
			<div class="icon-wrapper">
				<div
					@click="save"
					class="icon photo"
					v-if="selectedPage"
				>
					add_a_photo
				</div>
				<div
					v-else
					class="icon photo disable"
				>
					add_a_photo
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { defineComponent } from 'vue';
import { isUUID } from '@/tools/isUUID';
import apiService from '@/api/apiService';

export default defineComponent({
	name: 'reportPages',
	setup() {
		const route = useRoute();

		const name = ref('');
		const time = ref(null);
		const pages = ref([]);
		const selectedPage = ref('');
		const selectedItem = ref(null);

		const listPages = async () => {
			if (isUUID(route.params.id)) {
				const { success, objectPages } = await apiService.powerbi.bis.pages({ id: route.params.id });
				if (success) pages.value = objectPages;
			}
		};

		const selectPage = async (index, page) => {
			const { image } = await apiService.powerbi.bis.page({
				id: route.params.id,
				name: page.name,
				displayName: page.displayName,
			});
			if (page.id) {
				name.value = image.image_name;
				time.value = image.image_time;
			} else {
				name.value = page.displayName;
				time.value = 60;
			}
			selectedItem.value = index;
			selectedPage.value = page;
		};

		const save = async () => {
			const { success } = apiService.powerbi.bis.createImage({
				id: route.params.id,
				imageName: name.value,
				imageTime: time.value,
				pbiName: selectedPage.value.name,
				pbiDisplayName: selectedPage.value.displayName,
			});
			selectedPage.value = null;
			name.value = null;
			time.value = null;
			selectedItem.value = null;
			listPages();
		};

		const destroyImage = async (page) => {
			const { success } = apiService.powerbi.bis.destroyImage({
				report: route.params.id,
				name: page.name,
				displayName: page.displayName,
			});
			selectedPage.value = null;
			name.value = null;
			time.value = null;
			selectedItem.value = null;
			listPages();
		};

		onMounted(() => {
			listPages();
		});

		return {
			name,
			time,
			pages,
			selectedItem,
			selectedPage,
			save,
			selectPage,
			destroyImage,
		};
	},
});
</script>

<style lang="scss" scoped>
.pbi-object-pages {
	width: 100%;
	margin-top: 1rem * $phi;
	.title {
		font-weight: bold;
		color: $text-color;
		padding-left: 1rem;
		margin-bottom: 1rem;
		font-size: 1rem * $phi-sr;
		padding-bottom: 1rem * $phi-down;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	}
	.list {
		border-radius: 4px;
		background: rgba(255, 255, 255, 0.5);
		border: 1px solid rgba(0, 0, 0, 0.1);

		.group {
			margin: 0;
			padding: 0;
			list-style: none;

			height: 10rem;
			overflow-y: auto;

			border-top: 1px solid rgba(0, 0, 0, 0.1);
			background-color: rgba(255, 255, 255, 0.9);
			border-bottom: 1px solid rgba(0, 0, 0, 0.1);

			.page {
				display: flex;
				cursor: pointer;
				font-weight: bold;
				color: $text-color;
				padding-left: 2rem;
				align-items: center;
				gap: 1rem * $phi-sr;
				font-size: 1rem * $phi-sr;
				padding: 1rem * $phi-down;
				transition: all 0.2s ease;
				border-bottom: 1px solid rgba(0, 0, 0, 0.1);

				&:hover {
					background-color: rgba(0, 0, 0, 0.05);
				}

				.icon {
					font-size: 1.38rem;
					font-weight: normal;
					&.delete {
						color: $danger-color;
					}
					&.pending {
						color: $warning-color;
					}
				}
			}

			.page-name {
				flex-grow: 1;
				display: flex;
				cursor: pointer;
				color: $text-color;
				align-items: center;

				&.empty {
					padding: 1rem;
				}

				.icon {
					margin-right: 1rem;
					font-size: 1.38rem;
					color: rgba(0, 0, 0, 0.1);

					&.hasPhoto {
						color: $success-color;
					}

					.destroy-image {
						cursor: pointer;
						color: $warning-color;
					}
					.pending-image {
						color: rgba(0, 0, 0, 0.4);
					}
				}
			}
		}
	}
	.page-builder {
		gap: 1rem;
		display: flex;
		flex-direction: row;
		align-items: center;
		margin-top: 1rem * $phi;
		padding: 0 calc(1rem * $phi);

		.name {
			flex: 4;
		}

		.time {
			flex: 1;
		}

		.icon-wrapper {
			flex: 0 0 auto;
			padding-top: 1rem * $phi-sr;
		}

		.photo {
			font-size: 1rem * $phi-up;
			cursor: pointer;
			color: $success-color;

			&.disable {
				cursor: default;
				color: rgba(0, 0, 0, 0.3);
			}
		}
	}
}
</style>
