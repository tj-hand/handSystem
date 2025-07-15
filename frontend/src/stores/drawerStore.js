import { defineStore } from 'pinia';
import { ref, computed, markRaw } from 'vue';

export const useDrawerStore = defineStore('drawer', () => {
	const drawers = ref([]);

	const openDrawer = (component, width = 400, parentId = null, props = null) => {
		const newDrawer = {
			props: props,
			width,
			parentId,
			isOpen: true,
			id: Date.now().toString(),
			component: markRaw(component),
		};
		drawers.value.push(newDrawer);
		return newDrawer.id;
	};

	const closeDrawer = (id) => {
		const index = drawers.value.findIndex((d) => d.id === id);
		if (index !== -1) {
			const children = drawers.value.filter((d) => d.parentId === id);
			children.forEach((child) => closeDrawer(child.id));
			drawers.value[index].isOpen = false;
			setTimeout(() => {
				drawers.value = drawers.value.filter((d) => d.id !== id);
			}, 300);
		}
	};

	const closeAllDrawers = () => {
		drawers.value.forEach((drawer) => {
			drawer.isOpen = false;
		});
		setTimeout(() => {
			drawers.value = [];
		}, 300);
	};

	const currentDrawer = computed(() => {
		return drawers.value.length > 0 ? drawers.value[drawers.value.length - 1] : null;
	});

	return {
		drawers,
		openDrawer,
		closeDrawer,
		closeAllDrawers,
		currentDrawer,
	};
});
