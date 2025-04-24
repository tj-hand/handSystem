<template>
	<aside :class="{ 'expanded-menu': expandedMenu, 'user-menu-on': showUserMenu }">
		<div class="top-section">
			<div class="logo-wrapper">
				<Logo
					class="logo"
					@click="goHome()"
					version="inverse"
					:isIcon="!expandedMenu"
				/>
			</div>
			<ToogleMenu />
		</div>
	</aside>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import Logo from '@/components/Logo.vue';
import ToogleMenu from './ToogleMenu.vue';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'LeftMenu',
	components: {
		Logo,
		ToogleMenu,
	},
	setup() {
		const uiStore = useUIStore();
		const authStore = useAuthStore();

		const goHome = () => {
			router.push({ name: authStore.profileData.home_page });
		};

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		return { expandedMenu, goHome };
	},
});
</script>

<style lang="scss" scoped>
aside {
	display: flex;
	overflow-y: auto;
	min-height: 100vh;
	overflow-x: hidden;
	align-items: center;
	box-sizing: border-box;
	flex-direction: column;
	transition: width 1s ease;
	justify-content: space-between;
	border-right: 2px solid #ffffff;
	background-color: $secondary-color;
	box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);

	&.expanded-menu {
		.top-section {
			animation: showWithDelay 2s forwards;
			.logo-wrapper {
				margin: 1em 0;
				display: flex;
				justify-content: flex-start;
				// margin-left: calc(100% * list.nth($gr, 5));
				// padding-bottom: calc(1em * list.nth($gr, 2));
				border-bottom: 1px solid rgba(255, 255, 255, 0.35);
				.logo {
					cursor: pointer;
					// width: calc(100% * list.nth($gr, 1));
					// margin: calc(100% * list.nth($gr, 7)) 0;
				}
			}
		}
		.bottom-section {
			animation: showWithDelay 2s forwards;
		}
	}

	&:not(.expanded-menu) {
		.top-section,
		.bottom-section {
			animation: growWithDelay 2s forwards;
		}
	}

	.top-section {
		width: 100%;
		display: flex;
		max-width: 100%;
		flex-direction: column;
		justify-content: center;

		.logo-wrapper {
			display: flex;
			justify-content: center;
			// margin-top: calc(100% * list.nth($gr, 2));
			// margin-bottom: calc(100% * list.nth($gr, 2));
			.logo {
				margin: auto;
				// cursor: pointer;
				// width: calc(100% * list.nth($gr, 1));
			}
		}
	}

	.bottom-section {
		width: 100%;
		display: flex;
		max-width: 100%;
		flex-direction: column;
		justify-content: center;
		animation: fadeSlide 2s forwards;
	}
}

.user-menu {
	bottom: 0;
	position: absolute;
	transition: all 0.5s ease;
}

@media (max-width: 480px) {
	aside {
		&.expanded-menu {
			z-index: 100;
			position: absolute;

			&.user-menu-on {
				border: 0;
				overflow-y: hidden;
			}
		}
	}
	.user-menu {
		&.expanded {
			bottom: 4em;
			z-index: 101;
			position: fixed;
			left: 0 !important;
			width: 100% !important;
			transition: all 0.25s ease;
		}
	}
}

@media (max-height: 768px) {
	aside {
		overflow-y: scroll;
	}
}

aside::-webkit-scrollbar {
	width: 5px;
}

aside::-webkit-scrollbar-track {
	background: rgba(255, 255, 255, 0.9);
}

aside::-webkit-scrollbar-thumb {
	background: rgba(255, 255, 255, 0.8);
}

aside::-webkit-scrollbar-thumb:hover {
	background: rgba(255, 255, 255, 0.7);
}

.slide-menu-enter-active,
.slide-menu-leave-active {
	overflow: hidden;
	transition: max-height 0.3s ease-in-out;
}

.slide-menu-enter-from {
	max-height: 0;
}

.slide-menu-enter-to {
	max-height: calc(100vh - 4em);
}

.slide-menu-leave-from {
	max-height: calc(100vh - 4em);
}

.slide-menu-leave-to {
	max-height: 0;
}

.slide-menu-enter-active {
	animation: slideIn 0.3s ease-out;
}

.slide-menu-leave-active {
	animation: slideOut 0.3s ease-in;
}
</style>
