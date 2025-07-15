import apiClient from '@/api/apiClient';

const apiRequest = async (method, endpoint, params = {}, responseType = 'json') => {
	try {
		const config = { method, url: endpoint, responseType };
		method === 'GET' ? (config.params = params) : (config.data = params);
		if (params instanceof FormData) config.headers = { Accept: 'application/json' };
		const response = await apiClient(config);
		if (responseType === 'blob') return response;
		return response.data;
	} catch (error) {
		return { success: false, message: error.response?.data?.error || error };
	}
};

export default apiRequest;
