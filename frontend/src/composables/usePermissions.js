import { useAuthStore } from '@/stores/useAuthStore';

export function usePermissions(module) {
	const authStore = useAuthStore();

	const can = (action) => {
		return authStore.enviroment?.permissions[module]?.some(
			(p) => p.identifier === `auth.${module.toLowerCase()}.${action}`
		);
	};

	return { can };
}
