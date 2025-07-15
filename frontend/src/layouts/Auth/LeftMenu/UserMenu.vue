<template>
	<div
		ref="menuRef"
		class="user-menu"
		:class="{ 'expanded-menu': expandedMenu }"
	>
		<div
			@click="authUserData"
			class="menu-item"
		>
			<div class="icon">id_card</div>
			<div
				class="menu-label"
				v-if="expandedMenu"
			>
				{{ $t('auth.left_menu.user_menu.access_settings') }}
			</div>
		</div>
		<div
			@click="logout"
			class="menu-item logout"
		>
			<div class="icon">logout</div>
			<div
				class="menu-label"
				v-if="expandedMenu"
			>
				{{ $t('auth.left_menu.user_menu.safely_logout') }}
			</div>
		</div>
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import { useUIStore } from '@/stores/useUIStore';
import { onMounted, onBeforeUnmount } from 'vue';
import { closeEnviroment } from '@/services/closeEnviromentService';

export default defineComponent({
	name: 'UserMenu',
	emits: ['close'],
	setup(props, { emit }) {
		const menuRef = ref(null);
		const router = useRouter();
		const uiStore = useUIStore();

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const logout = () => {
			uiStore.setUserMenu(false);
			closeEnviroment(router);
		};

		const authUserData = async () => {
			uiStore.setUserMenu(false);
			await router.push({ name: 'authenticatedUser' });
		};

		const handleClickOutside = (event) => {
			if (menuRef.value && !menuRef.value.contains(event.target)) emit('close');
		};

		onMounted(() => {
			document.addEventListener('click', handleClickOutside);
		});

		onBeforeUnmount(() => {
			document.removeEventListener('click', handleClickOutside);
		});

		return { menuRef, expandedMenu, logout, authUserData };
	},
});
</script>

<style lang="scss" scoped>
.user-menu {
	z-index: 10;
	display: flex;
	align-items: center;
	flex-direction: column;
	font-size: 1rem * $phi;
	justify-content: center;
	background-color: #ffffff;
	border-top: 1px solid $secondary-color;
	border-right: 1px solid $secondary-color;

	.menu-item {
		width: 100%;
		display: flex;
		cursor: pointer;
		flex-direction: row;
		align-items: center;
		padding: 1rem * $phi;
		box-sizing: border-box;
		color: $secondary-color;
		justify-content: center;
		transition: all 0.5s ease;

		.icon {
			font-size: 1rem * $phi-up;
		}

		&.logout {
			margin-top: 1rem * $phi;
			padding: 1rem (1rem * $phi);
			border-top: 1px solid $primary-color;
		}
		&:hover {
			color: $primary-color;
		}
	}

	&.expanded-menu {
		justify-content: flex-start;
		.menu-item {
			justify-content: flex-start;
			.menu-label {
				margin-left: 1rem * $phi-down;
			}
			&:hover {
				background-color: rgba(0, 0, 0, 0.05);
			}
		}
	}
}

@media (max-width: 480px) {
	.user-menu {
		&.expanded-menu {
			padding-top: calc(100vh - 4em);
		}
	}
}
</style>
