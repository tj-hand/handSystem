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
		show(id) {
			return apiRequest('POST', endpoints.user.show, id);
		},
		delete(id) {
			return apiRequest('POST', endpoints.user.delete, id);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.user.upsert, data);
		},
		exists(email) {
			return apiRequest('POST', endpoints.user.exists, email);
		},
		addToAccount(email) {
			return apiRequest('POST', endpoints.user.addToAccount, email);
		},
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
		users() {
			return apiRequest('POST', endpoints.account.users);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.account.upsert, data);
		},
	},
};

export default apiService;
