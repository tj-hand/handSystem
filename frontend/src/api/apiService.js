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
		sendInvite(id) {
			return apiRequest('POST', endpoints.user.sendInvite, id);
		},
		associated_with_clients(id) {
			return apiRequest('POST', endpoints.user.associated_with_clients, id);
		},
		associated_with_groups(id) {
			return apiRequest('POST', endpoints.user.associated_with_groups, id);
		},
		associated_with_actions(id) {
			return apiRequest('POST', endpoints.user.associated_with_actions, id);
		},
	},
	group: {
		show(id) {
			return apiRequest('POST', endpoints.group.show, id);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.group.upsert, data);
		},
		delete(id) {
			return apiRequest('POST', endpoints.group.delete, id);
		},
		associated_users(id) {
			return apiRequest('POST', endpoints.group.associated_users, id);
		},
		associated_actions(id) {
			return apiRequest('POST', endpoints.group.associated_actions, id);
		},
	},
	client: {
		show(id) {
			return apiRequest('POST', endpoints.client.show, id);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.client.upsert, data);
		},
		delete(id) {
			return apiRequest('POST', endpoints.client.delete, id);
		},
		associated_users(id) {
			return apiRequest('POST', endpoints.client.associated_users, id);
		},
	},
	account: {
		show(id) {
			return apiRequest('POST', endpoints.account.show, id);
		},
		delete(id) {
			return apiRequest('POST', endpoints.account.delete, id);
		},
		users() {
			return apiRequest('POST', endpoints.account.users);
		},
		groups() {
			return apiRequest('POST', endpoints.account.groups);
		},
		clients() {
			return apiRequest('POST', endpoints.account.clients);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.account.upsert, data);
		},
	},
	authorization: {
		queue() {
			return apiRequest('POST', endpoints.authorization.queue);
		},
		set(data) {
			return apiRequest('POST', endpoints.authorization.set, data);
		},
	},
};

export default apiService;
