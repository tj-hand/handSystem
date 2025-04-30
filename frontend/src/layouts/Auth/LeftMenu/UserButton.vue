<template>
	<div class="userButtonCotainer">
		<div
			v-if="expandedMenu"
			class="user-button-wrapper expanded-menu"
		>
			<div class="user-button">{{ userInitials }}</div>
			<div class="user-name">{{ clipText(userName, 20) }}</div>
		</div>
		<div
			v-else
			class="user-button-wrapper"
		>
			<div class="user-button">{{ userInitials }}</div>
		</div>
	</div>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import { clipText } from '@/tools/clipText';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'UserButton',
	setup() {
		const uiStore = useUIStore();
		const authStore = useAuthStore();

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const userName = computed(() => {
			return authStore.enviroment.user?.name + ' ' + authStore.enviroment.user?.lastname;
		});
		const userInitials = computed(() => {
			const name = authStore.enviroment.user?.name || '';
			const lastname = authStore.enviroment.user?.lastname || '';
			return name.charAt(0) + lastname.charAt(0);
		});

		return { userName, userInitials, expandedMenu, clipText };
	},
});
</script>

<style lang="scss" scoped>
.user-button-wrapper {
	width: 100%;
	display: flex;
	padding: 1em 0;
	cursor: pointer;
	align-items: center;
	justify-content: center;
	border-top: 1px solid rgba(255, 255, 255, 0.35);

	&:hover {
		background-color: rgba(255, 255, 255, 0.2);
	}

	&.expanded-menu {
		padding-left: 1rem;
		justify-content: flex-start;
		.user-name {
			color: #ffffff;
			margin-left: 1rem * $phi;
		}
	}

	.user-button {
		width: 2em;
		height: 2em;
		display: flex;
		font-size: 1em;
		color: #ffffff;
		line-height: 2em;
		border-radius: 2em;
		align-content: center;
		justify-content: center;
		background-color: rgba(255, 255, 255, 0.35);
	}
}
</style>
