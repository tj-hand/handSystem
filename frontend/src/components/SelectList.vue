<template>
	<div
		class="select-list"
		:style="'width: ' + responsive() + 'px;'"
	>
		<div class="title">{{ $t(title) }}</div>
		<div class="search-wrapper">
			<div class="form-element-wrapper">
				<input
					type="text"
					v-model="search"
					:placeholder="$t('generic.search')"
				/>
				<div class="icon">search</div>
			</div>
			<div
				class="add-button"
				@click="addAction"
				v-if="addPermission"
			>
				<div class="icon">{{ addIcon }}</div>
			</div>
		</div>
		<div class="list-wrapper">
			<div
				class="list"
				ref="listContainer"
			>
				<div
					class="item"
					:key="item[idField] || index"
					@click="goTo(item[idField])"
					v-if="filteredItems.length > 0"
					v-for="(item, index) in filteredItems"
					:class="{ active: isItemActive(item[idField]), not_authorized: item['not_authorized'] }"
					:ref="
						(el) => {
							if (isItemActive(item[idField])) activeItemRef = el;
						}
					"
				>
					<div class="mainContent">{{ item[mainContent] }}</div>
					<div class="subContent">{{ item[subContent] }}</div>
				</div>
				<div
					v-if="filteredItems.length === 0"
					class="empty-message"
				>
					{{ search ? $t('generic.no_results_found') : $t('generic.no_items_available') }}
				</div>
			</div>
		</div>
	</div>
</template>
<script>
import { defineComponent } from 'vue';
import { onMounted, nextTick } from 'vue';
import { ref, computed, watch } from 'vue';
import { is_mobile } from '@/tools/screenSizes';
import { closest } from '@/tools/harmonize.js';
import { useRouter, useRoute } from 'vue-router';

export default defineComponent({
	name: 'SelectList',
	props: {
		title: { type: String, default: '' },
		list: { type: Array, required: true },
		width: { type: Number, default: 300 },
		idField: { type: String, default: 'id' },
		subContent: { type: String, default: 'id' },
		addIcon: { type: String, default: 'add_circle' },
		mainContent: { type: String, default: 'name' },
		addPermission: { type: Boolean, default: false },
	},
	emits: ['addAction'],
	setup(props, { emit }) {
		const search = ref('');
		const route = useRoute();
		const router = useRouter();
		const activeItemRef = ref(null);
		const listContainer = ref(null);
		const navigationInProgress = ref(false);

		const activeItemId = computed(() => route.params.id);

		const filteredItems = computed(() => {
			const items = Object.values(props.list);
			const query = (search.value ?? '').toLowerCase();

			return items.filter((item) => {
				const main = (item[props.mainContent] ?? '').toLowerCase();
				const sub = (item[props.subContent] ?? '').toLowerCase();
				return main.includes(query) || sub.includes(query);
			});
		});

		const isItemActive = (uuid) => {
			return uuid === activeItemId.value || uuid === route.params.id;
		};

		const goTo = async (id) => {
			if (navigationInProgress.value) return;
			navigationInProgress.value = true;
			try {
				await router.push({ name: route.name, params: { ...route.params, id: id } });
			} catch (err) {
				// console.log('Navigation was prevented');
			} finally {
				navigationInProgress.value = false;
			}
		};

		const scrollActiveItemIntoView = async () => {
			await nextTick();
			if (activeItemRef.value && listContainer.value) {
				activeItemRef.value.scrollIntoView({
					behavior: 'smooth',
					block: 'nearest',
				});
			}
		};

		const menuWidth = computed(() => {
			return closest(window.innerWidth, props.width);
		});

		const responsive = () => {
			return is_mobile() ? 'calc(100% - 2rem)' : menuWidth.value;
		};

		const addAction = () => {
			emit('addAction');
		};

		onMounted(() => {
			if (activeItemId.value && activeItemId.value !== 'new') {
				setTimeout(scrollActiveItemIntoView, 100);
			}
		});

		watch([activeItemId, () => filteredItems.value.length], () => {
			if (activeItemId.value && activeItemId.value !== 'new') scrollActiveItemIntoView();
		});

		watch(search, () => {
			nextTick(() => {
				if (activeItemId.value && activeItemId.value !== 'new') {
					const itemExists = filteredItems.value.some((item) => item[props.idField] === activeItemId.value);

					if (itemExists) {
						scrollActiveItemIntoView();
					}
				}
			});
		});

		return { search, filteredItems, listContainer, activeItemRef, goTo, addAction, isItemActive, responsive };
	},
});
</script>

<style scoped lang="scss">
.select-list {
	width: 100%;
	flex-grow: 0;
	display: flex;
	border-radius: 4px;
	flex-direction: column;
	margin: 1rem 0 1rem 1rem;
	height: calc(100% - 2rem);
	border: 1px solid rgba(0, 0, 0, 0.1);
	background: rgba($primary-color, 0.03);

	.title {
		font-size: 1rem;
		color: #ffffff;
		font-weight: bold;
		padding: 1rem * $phi;
		border-radius: 4px 4px 0 0;
		background: rgba($secondary-color, 0.75);
	}

	.search-wrapper {
		display: flex;
		flex-direction: row;
		align-items: center;
		padding: 1rem * $phi-down;
		background: rgba($secondary-color, 0.5);

		.add-button {
			cursor: pointer;
			color: #ffffff;
			font-size: 1rem * $phi-up;
			margin-left: 1rem * $phi-down;
		}

		.form-element-wrapper {
			width: 100%;
			display: flex;
			align-items: center;
			border-radius: 4px;
			box-sizing: border-box;
			transition: all 0.5s ease;
			padding: 1rem * $phi-down;
			justify-content: space-between;
			background: $form-wrapper-backgorund-color;
			border: 1px solid $form-wrapper-border-color;
			box-shadow: 2px 2px 5px inset rgba(0, 0, 0, 0.05);
			.icon {
				font-size: 1.25rem;
				color: $text-color;
			}
			input {
				border: 0;
				outline: 0;
				flex-grow: 1;
				font-size: 1rem;
				color: $text-color;
				background-color: transparent;
			}
		}
	}

	.list-wrapper {
		flex-grow: 1;
		overflow: hidden;
		position: relative;

		.list {
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			overflow-y: auto;
			position: absolute;
			overflow-x: hidden;

			.empty-message {
				padding: 2rem 0;
				text-align: center;
				font-size: 1rem * $phi-sr;
				color: rgba(0, 0, 0, 0.5);
			}

			.item {
				padding: 1rem;
				cursor: pointer;
				color: $text-color;
				transition: all 0.5s ease;
				background-color: rgba(0, 0, 0, 0.05);
				border-left: 3px solid rgba(0, 0, 0, 0.05);
				border-bottom: 1px solid rgba(0, 0, 0, 0.1);

				&:hover {
					background-color: rgba(0, 0, 0, 0.05);
				}

				&.active {
					color: $primary-color;
					background-color: #ffffff;
					border-left: 3px solid $primary-color;
				}

				.mainContent {
					font-weight: bold;
				}

				.subContent {
					font-size: 1rem * $phi-sr;
				}

				&.not_authorized {
					border-left-color: rgba($warning-color, 0.4);
				}
			}
		}
	}
}

@media only screen and (max-width: 768px) {
	.select-list {
		margin-right: 1rem;
	}
}
</style>
