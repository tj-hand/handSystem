import apiService from '@/api/apiService';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { logoutService } from '@/services/logoutService';

export async function getProfile(router) {
	const UIStore = useUIStore();
	const authStore = useAuthStore();
	authStore.clearProfile();
	const response = await apiService.auth.login();
	if (response.success) {
		authStore.setProfile(response.profile);
		authStore.setAuthenticated(true);
		return { success: true };
	} else {
		if (UIStore.isLogin) return { success: false, message: response.message };
		logoutService(router);
	}
}
