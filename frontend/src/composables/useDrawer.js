import { useDrawerStore } from '@/stores/drawerStore';

export const useDrawer = () => {
	const drawerStore = useDrawerStore();

	const openDrawer = (component, width = 400, props) => {
		const currentDrawer = drawerStore.currentDrawer;
		const parentId = currentDrawer ? currentDrawer.id : null;
		return drawerStore.openDrawer(component, width, parentId, props);
	};

	const closeDrawer = (id) => {
		drawerStore.closeDrawer(id);
	};

	const closeCurrentDrawer = () => {
		if (drawerStore.currentDrawer) {
			drawerStore.closeDrawer(drawerStore.currentDrawer.id);
		}
	};

	const closeAllDrawers = () => {
		drawerStore.closeAllDrawers();
	};

	return {
		openDrawer,
		closeDrawer,
		closeCurrentDrawer,
		closeAllDrawers,
	};
};
