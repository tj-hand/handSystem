import apiService from '@/api/apiService';
import { useAuthStore } from '@/stores/useAuthStore';

export async function closeEnviroment(router) {
	const authStore = useAuthStore();
	authStore.setAuthenticated(false);
	authStore.clearEnviroment();
	await apiService.auth.revokeTokens();
	router.push({ name: 'Login' });
}
