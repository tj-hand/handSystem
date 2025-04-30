import endpoints from '@/api/endpoints';
import apiRequest from '@/api/apiRequest';

const apiService = {
	auth: {
		requestTokens(credentials) {
			return apiRequest('POST', endpoints.auth.requestTokens, credentials);
		},
		revokeTokens() {
			return apiRequest('POST', endpoints.auth.revokeTokens);
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
	user: {
		requestEnviroment() {
			return apiRequest('POST', endpoints.user.requestEnviroment);
		},
		updateScope(data) {
			return apiRequest('POST', endpoints.user.updateScope, data);
		},
	},
	account: {
		show(id) {
			return apiRequest('POST', endpoints.account.show, id);
		},
	},
};

export default apiService;
