import axios from 'axios';
import { useUIStore } from '@/stores/useUIStore';

const uiStore = useUIStore();
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://apihand.dotmkt.com.br/';

let isRefreshing = false;
let refreshSubscribers = [];

const subscribeTokenRefresh = (cb) => {
	refreshSubscribers.push(cb);
};

const onRefreshed = (token) => {
	refreshSubscribers.forEach((cb) => cb(token));
	refreshSubscribers = [];
};

const apiClient = axios.create({
	baseURL: BASE_URL,
	withCredentials: true,
	headers: {
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
	async (response) => {
		uiStore.hideSpinner();
		return response;
	},
	async (error) => {
		uiStore.hideSpinner();
		return Promise.reject(error);
	}
);

export default apiClient;
