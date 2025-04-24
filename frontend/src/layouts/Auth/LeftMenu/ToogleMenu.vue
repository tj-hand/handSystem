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
import { useUIStore } from '@/stores/useUIStore';

export default defineComponent({
	name: 'ToogleMenu',
	setup() {
		const uiStore = useUIStore();

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const toogleMenu = () => {
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
	color: rgba(255, 255, 255, 0.35);
	// font-size: calc(1em / list.nth($gr, 1));
	.expanded-content {
		display: flex;
		flex-direction: row;
		align-items: center;

		.toogle-text {
			width: 0;
			margin-left: 0;
			overflow: hidden;
			white-space: nowrap;
			transition: width 0.5s ease;
			// font-size: calc(1em * list.nth($gr, 2));
		}
	}
}

.expanded-menu {
	.toogle-menu {
		padding-left: 1em;
		justify-content: flex-start;
		.toogle-text {
			width: 100%;
			transition: width 0.5s ease 0.5s;
		}
	}
}
</style>
