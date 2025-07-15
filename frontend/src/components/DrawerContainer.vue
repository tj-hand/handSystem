<template>
	<div class="drawer-container">
		<transition name="fade">
			<div
				v-if="currentDrawer"
				class="drawer-overlay"
				:style="{ backdropFilter: `blur(${overlayBlur})` }"
			></div>
		</transition>

		<div class="drawers-stack">
			<transition-group name="slide">
				<div
					:key="drawer.id"
					class="drawer-wrapper"
					v-for="drawer in drawers"
					:style="{
						width: calculateDrawerWidth(drawer) + 'px',
						zIndex: 1000 + drawers.indexOf(drawer),
					}"
				>
					<div
						class="drawer-content"
						v-show="drawer.isOpen"
					>
						<component
							:is="drawer.component"
							v-bind="drawer.props"
							@close="() => closeDrawer(drawer.id)"
						/>
					</div>
				</div>
			</transition-group>
		</div>
	</div>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import { useDrawerStore } from '@/stores/drawerStore';

export default defineComponent({
	name: 'DrawerContainer',
	props: {
		overlayBlur: {
			type: String,
			default: '2px',
		},
	},
	setup() {
		const drawerStore = useDrawerStore();

		const drawers = computed(() => drawerStore.drawers);
		const currentDrawer = computed(() => drawerStore.currentDrawer);

		const calculateDrawerWidth = (drawer) => {
			if (!drawer.parentId) {
				if (drawer.width > window.innerWidth) drawer.width = window.innerWidth;
				return drawer.width;
			}
			const parent = drawers.value.find((d) => d.id === drawer.parentId);
			if (!parent) return drawer.width;
			return (parent.width * drawer.width) / 100;
		};

		const closeDrawer = (id) => {
			drawerStore.closeDrawer(id);
		};

		const closeCurrentDrawer = () => {
			if (currentDrawer.value) {
				closeDrawer(currentDrawer.value.id);
			}
		};

		return {
			drawers,
			currentDrawer,
			closeDrawer,
			calculateDrawerWidth,
			closeCurrentDrawer,
		};
	},
});
</script>

<style lang="scss" scoped>
.drawer-container {
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	position: fixed;
	z-index: 100000;
	pointer-events: none;

	.drawer-overlay {
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		position: absolute;
		pointer-events: all;
	}

	.drawers-stack {
		top: 0;
		right: 0;
		height: 100%;
		display: flex;
		position: absolute;
		pointer-events: none;
		flex-direction: row-reverse;

		.drawer-wrapper {
			top: 0;
			right: 0;
			height: 100%;
			position: absolute;
			pointer-events: all;
			background: $background-color;
			border-left: 1px solid rgba(0, 0, 0, 0.15);
			box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);

			.drawer-content {
				height: 100%;
				overflow-y: auto;
			}
		}
	}
}

.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
	transition: transform 0.3s ease;
}
.slide-enter-from {
	transform: translateX(100%);
}
.slide-leave-to {
	transform: translateX(100%);
}
</style>
