import apiService from '@/api/apiService';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { closeEnviroment } from '@/services/closeEnviromentService';

export async function requestEnviroment(router) {
	const UIStore = useUIStore();
	const authStore = useAuthStore();
	authStore.clearEnviroment();
	const response = await apiService.user.requestEnviroment();
	if (response.success) {
		authStore.setEnviroment(response.enviroment);
		authStore.setAuthenticated(true);
		return { success: true };
	} else {
		if (UIStore.isLogin) return { success: false, message: response.message };
		closeEnviroment(router);
	}
}
