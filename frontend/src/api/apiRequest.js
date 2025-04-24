import apiClient from '@/api/apiClient';

const apiRequest = async (method, endpoint, params = {}, responseType = 'json') => {
	try {
		const config = { method, url: endpoint, responseType };
		method === 'GET' ? (config.params = params) : (config.data = params);
		const response = await apiClient(config);
		return response.data;
	} catch (error) {
		return { success: false, message: error.response?.data?.error || error };
	}
};

export default apiRequest;
