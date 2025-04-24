import apiService from '@/api/apiService';
import { useAuthStore } from '@/stores/useAuthStore';

export async function logoutService(router) {
	const authStore = useAuthStore();
	authStore.setAuthenticated(false);
	authStore.clearProfile();
	await apiService.auth.logout();
	router.push({ name: 'Login' });
}
