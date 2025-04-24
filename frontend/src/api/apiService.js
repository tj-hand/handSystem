import endpoints from '@/api/endpoints';
import apiRequest from '@/api/apiRequest';

const apiService = {
	auth: {
		getTokens(credentials) {
			return apiRequest('POST', endpoints.auth.getTokens, credentials);
		},
		login() {
			return apiRequest('POST', endpoints.auth.login);
		},
		logout() {
			return apiRequest('POST', endpoints.auth.logout);
		},
	},
	password: {
		requestReset(data) {
			return apiRequest('POST', endpoints.password.requestReset, data);
		},
		reset(data) {
			return apiRequest('POST', endpoints.password.reset, data);
		},
	},
};

export default apiService;
