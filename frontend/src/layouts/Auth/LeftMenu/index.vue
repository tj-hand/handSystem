<template>
	<aside
		:style="'width:' + menuWidth + 'px;'"
		:class="[
			expandedMenu ? 'expanded' : 'collapsed',
			is_mobile() ? 'mobile' : '',
			{ 'user-menu-on': showUserMenu },
		]"
	>
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
			<Modules
				group="intelligence"
				v-if="clientSelected"
			/>
			<Modules
				group="users"
				v-if="clientSelected"
			/>
			<Modules
				group="digital-signage"
				v-if="clientSelected"
			/>
			<Modules
				group="tools"
				v-if="clientSelected"
			/>
		</div>
		<div class="bottom-section">
			<Modules group="admin" />
			<UserButton @click.stop="toogleUserMenu" />
		</div>
	</aside>
	<transition name="slide-menu">
		<UserMenu
			class="user-menu"
			@close="closeMenu"
			v-if="showUserMenu"
			:class="{ expanded: expandedMenu }"
			:style="'left:' + menuWidth + 'px; width: ' + menuWidth * 0.618 + 'px;'"
		/>
	</transition>
</template>

<script>
import { computed } from 'vue';
import Modules from './Modules.vue';
import { defineComponent } from 'vue';
import UserMenu from './UserMenu.vue';
import { useRouter } from 'vue-router';
import Logo from '@/components/Logo.vue';
import ToogleMenu from './ToogleMenu.vue';
import UserButton from './UserButton.vue';
import { is_mobile } from '@/tools/screenSizes';
import { closest } from '@/tools/harmonize.js';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'LeftMenu',
	components: {
		Logo,
		Modules,
		UserMenu,
		ToogleMenu,
		UserButton,
	},
	setup() {
		const router = useRouter();
		const uiStore = useUIStore();
		const authStore = useAuthStore();

		const goHome = () => {
			is_mobile() ? uiStore.setExpandedMenu(false) : null;
			router.push({ name: authStore.enviroment.current_scope.home_page });
		};

		const clientSelected = computed(() => {
			return authStore.enviroment.current_scope?.client_id;
		});

		const menuWidth = computed(() => {
			const menuSize = expandedMenu.value ? 280 : 60;
			if (is_mobile() && expandedMenu.value) return window.innerWidth;
			return closest(window.innerWidth, menuSize);
		});

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const showUserMenu = computed(() => {
			return uiStore.userMenu;
		});

		const toogleUserMenu = () => {
			uiStore.setUserMenu(!uiStore.userMenu);
		};

		const closeMenu = () => {
			showUserMenu.value = false;
		};

		return { menuWidth, expandedMenu, showUserMenu, clientSelected, goHome, is_mobile, closeMenu, toogleUserMenu };
	},
});
</script>

<style lang="scss" scoped>
aside {
	z-index: 60;
	display: flex;
	overflow-y: auto;
	min-height: 100vh;
	overflow-x: hidden;
	align-items: center;
	box-sizing: border-box;
	flex-direction: column;
	transition: width 1s ease;
	justify-content: space-between;
	border-right: 1px solid #ffffff;
	box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
	background-color: rgba($secondary-color, 0.85);

	&.expanded {
		&.mobile {
			border-right: 0;
		}

		.top-section {
			animation: showWithDelay 2s forwards;
			.logo-wrapper {
				margin: 1em 0;
				display: flex;
				margin-left: 1rem;
				justify-content: flex-start;
				padding-bottom: 1rem * $phi;
				border-bottom: 1px solid rgba(255, 255, 255, 0.35);
				.logo {
					cursor: pointer;
					margin: 1rem 0;
					max-width: 15rem;
					width: 100% * $phi;
				}
			}
		}
		.bottom-section {
			animation: showWithDelay 2s forwards;
		}
	}

	&:not(.expanded) {
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
			margin-top: 1rem;
			margin-bottom: 1rem;
			.logo {
				margin: auto;
				cursor: pointer;
				width: 100% * $phi;
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

@media (max-height: 768px) {
	aside {
		border-right: 0;
		overflow-y: scroll;
	}

	aside::-webkit-scrollbar {
		width: 5px;
	}

	aside::-webkit-scrollbar-track {
		background: rgba(255, 255, 255, 0.5);
	}

	aside::-webkit-scrollbar-thumb {
		border-radius: 5px;
		background: rgba(255, 255, 255, 0.5);
	}

	aside::-webkit-scrollbar-thumb:hover {
		background: rgba(255, 255, 255, 0.5);
	}
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
