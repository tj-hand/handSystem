import axios from 'axios';
import { useUIStore } from '@/stores/useUIStore';

const uiStore = useUIStore();
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://apihand.dotmkt.com.br/';

const apiClient = axios.create({
	baseURL: BASE_URL,
	withCredentials: true,
	headers: {
		'Content-Type': 'application/json',
		Accept: 'application/json',
	},
});

apiClient.interceptors.request.use(
	async (config) => {
		uiStore.showSpinner();
		return config;
	},
	(error) => {
		uiStore.hideSpinner();
		return Promise.reject(error);
	}
);

apiClient.interceptors.response.use(
	(response) => {
		uiStore.hideSpinner();
		return response;
	},
	async (error) => {
		uiStore.hideSpinner();
		return Promise.reject(error);
	}
);

export default apiClient;
