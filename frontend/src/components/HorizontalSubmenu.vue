<template>
	<div
		class="horizontal-submenu"
		ref="menuContainer"
	>
		<div
			:key="index"
			class="item"
			@click="goTo(item.link_to, item.id, index)"
			v-for="(item, index) in filteredMenuItems"
			:class="{ active: isActive(item.link_to) }"
			:ref="
				(el) => {
					if (index === activeIndex) activeTabRef = el;
				}
			"
		>
			{{ $t(item.label) }}
		</div>
	</div>
</template>
<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { onMounted, nextTick, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'HorizontalSubmenu',
	props: {
		params: { type: Object },
	},
	setup(props) {
		const route = useRoute();
		const router = useRouter();
		const activeIndex = ref(null);
		const activeTabRef = ref(null);
		const menuContainer = ref(null);
		const authStore = useAuthStore();

		const filteredMenuItems = computed(() => {
			if (!props.params?.length) return [];

			return props.params.filter((item) => {
				if (!item.permissionRequired) return true;

				const { category, identifier } = item.permissionRequired;
				let permission;

				if (category === 'SuperUser') {
					permission = authStore.enviroment.user.superuser ? { is_visible: true } : null;
				} else if (category === 'AccountAdmin') {
					permission =
						authStore.enviroment.user.superuser || authStore.enviroment.user.account_admin
							? { is_visible: true }
							: null;
				} else {
					const categoryPermissions = authStore.enviroment?.permissions?.[category];
					if (!categoryPermissions) return false;
					permission = categoryPermissions.find((p) => p.identifier === identifier);
				}

				return permission?.is_visible === true;
			});
		});

		const scrollActiveTabIntoView = async () => {
			await nextTick();
			if (activeTabRef.value && menuContainer.value) {
				const container = menuContainer.value;
				const tab = activeTabRef.value;

				const containerRect = container.getBoundingClientRect();
				const tabRect = tab.getBoundingClientRect();

				const isFullyVisible = tabRect.left >= containerRect.left && tabRect.right <= containerRect.right;

				if (!isFullyVisible) {
					const scrollLeft = tab.offsetLeft - container.clientWidth / 2 + tab.offsetWidth / 2;
					container.scrollTo({
						left: scrollLeft,
						behavior: 'smooth',
					});
				}
			}
		};

		const goTo = async (page, id, index) => {
			try {
				const routeOptions = { name: page };
				if (id !== null && id !== undefined) routeOptions.params = { id };
				await router.push(routeOptions);
				activeIndex.value = index;
			} catch (navigationError) {
				// Navigation was cancelled - no action needed
			}
		};

		const isActive = (routeName) => {
			return (
				route.name === routeName || (route.matched && route.matched.some((record) => record.name === routeName))
			);
		};

		onMounted(() => {
			if (filteredMenuItems.value?.length) {
				const currentRouteName = route.name;
				const foundIndex = filteredMenuItems.value.findIndex(
					(item) =>
						item.link_to === currentRouteName ||
						(route.matched && route.matched.some((record) => record.name === item.link_to))
				);

				if (foundIndex !== -1) {
					activeIndex.value = foundIndex;
					nextTick(() => {
						scrollActiveTabIntoView();
					});
				} else if (filteredMenuItems.value.length > 0) {
					goTo(filteredMenuItems.value[0].link_to, 0);
				}
			}
		});

		watch(activeIndex, () => {
			scrollActiveTabIntoView();
		});

		watch(
			() => route.name,
			() => {
				if (filteredMenuItems.value?.length) {
					const currentRouteName = route.name;
					const foundIndex = filteredMenuItems.value.findIndex(
						(item) =>
							item.link_to === currentRouteName ||
							(route.matched && route.matched.some((record) => record.name === item.link_to))
					);

					if (foundIndex !== -1 && foundIndex !== activeIndex.value) {
						activeIndex.value = foundIndex;
					}
				}
			}
		);

		return { activeIndex, activeTabRef, menuContainer, filteredMenuItems, goTo, isActive };
	},
});
</script>

<style scoped lang="scss">
.horizontal-submenu::-webkit-scrollbar {
	height: 5px;
}

.horizontal-submenu {
	width: 100%;
	display: flex;
	flex-shrink: 0;
	padding: 0 1rem;
	overflow-x: auto;
	overflow-y: hidden;
	color: $text-color;
	flex-direction: row;
	box-sizing: border-box;
	background-color: rgba(255, 255, 255, 1);
	box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
	scroll-behavior: smooth;
	.item {
		z-index: 1;
		cursor: pointer;
		font-weight: bold;
		text-wrap: nowrap;
		margin-bottom: -2px;
		font-size: 1rem * $phi-sr;
		transition: all 0.5s ease;
		border-radius: 4px 4px 0 0;
		padding: (1rem * $phi) (1rem * $phi-up);
		background-color: rgba(0, 0, 0, 0.03);
		border-top: 1px solid rgba(0, 0, 0, 0.06);
		border-right: 1px solid rgba(0, 0, 0, 0.06);
		border-bottom: 1px solid rgba(0, 0, 0, 0.03);
		&:first-child {
			border-left: 1px solid rgba(0, 0, 0, 0.06);
		}
		&:last-child {
			border-right: 1px solid rgba(0, 0, 0, 0.06);
		}
		&:hover {
			color: $primary-color;
			background-color: rgba($primary-color, 0.15);
		}
		&.active {
			font-size: 1rem;
			color: $primary-color;
			background-color: #ffffff;
			border-top: 1px solid $primary-color;
			border-left: 1px solid $primary-color;
			border-right: 1px solid $primary-color;
			box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
		}
	}
}
</style>
