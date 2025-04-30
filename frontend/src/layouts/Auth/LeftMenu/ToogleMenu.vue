<template>
	<div
		class="toogle-menu"
		@click="toogleMenu"
	>
		<div
			v-if="expandedMenu"
			class="expanded-content"
		>
			<div class="icon">left_panel_close</div>
			<div class="toogle-text">{{ $t('auth.left_menu.collapse') }}</div>
		</div>
		<div v-else>
			<div class="icon">left_panel_open</div>
		</div>
	</div>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import { mobile } from '@/tools/screenSizes';
import { useUIStore } from '@/stores/useUIStore';

export default defineComponent({
	name: 'ToogleMenu',
	setup() {
		const uiStore = useUIStore();

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const toogleMenu = () => {
			if (mobile() && !expandedMenu.value) uiStore.setScopeSelector(false);
			uiStore.toggleExpandedMenu();
		};

		return { expandedMenu, toogleMenu };
	},
});
</script>

<style lang="scss" scoped>
.toogle-menu {
	margin: 0;
	width: 100%;
	display: flex;
	max-width: 100%;
	cursor: pointer;
	margin-bottom: 1em;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	font-size: 1rem;
	color: rgba(255, 255, 255, 0.35);
	.expanded-content {
		display: flex;
		flex-direction: row;
		align-items: center;

		.toogle-text {
			width: 0;
			margin-left: 0;
			padding-top: 3px;
			overflow: hidden;
			white-space: nowrap;
			transition: width 0.5s ease;
		}
	}
}

.expanded {
	.toogle-menu {
		padding: 0 1rem;
		justify-content: flex-start;
		.toogle-text {
			width: 100%;
			font-size: 1rem * $phi;
			transition: width 0.5s ease 0.5s;
		}
	}
}
</style>
