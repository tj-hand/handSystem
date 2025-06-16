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
		existsInClient(email) {
			return apiRequest('POST', endpoints.user.existsInClient, email);
		},
		addToClient(email) {
			return apiRequest('POST', endpoints.user.addToClient, email);
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
		local_associated_with_groups(id) {
			return apiRequest('POST', endpoints.user.local_associated_with_groups, id);
		},
		associated_with_global_actions(id) {
			return apiRequest('POST', endpoints.user.associated_with_global_actions, id);
		},
		associated_with_local_actions(id) {
			return apiRequest('POST', endpoints.user.associated_with_local_actions, id);
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
	profile: {
		show(id) {
			return apiRequest('POST', endpoints.profile.show, id);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.profile.upsert, data);
		},
		delete(id) {
			return apiRequest('POST', endpoints.profile.delete, id);
		},
		associated_users(id) {
			return apiRequest('POST', endpoints.profile.associated_users, id);
		},
		associated_objects(id) {
			return apiRequest('POST', endpoints.profile.associated_objects, id);
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
		associated_workspaces(id) {
			return apiRequest('POST', endpoints.client.associated_workspaces, id);
		},
		local_users(id) {
			return apiRequest('POST', endpoints.client.local_users, id);
		},
		profiles() {
			return apiRequest('POST', endpoints.client.profiles);
		},
		files() {
			return apiRequest('POST', endpoints.client.files);
		},
		signages() {
			return apiRequest('POST', endpoints.client.signages);
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
		workspaces() {
			return apiRequest('POST', endpoints.account.workspaces);
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
	powerbi: {
		sync() {
			return apiRequest('POST', endpoints.powerbi.sync);
		},
		workspace: {
			show(id) {
				return apiRequest('POST', endpoints.powerbi.workspace.show, id);
			},
			upsert(id) {
				return apiRequest('POST', endpoints.powerbi.workspace.upsert, id);
			},
			delete(id) {
				return apiRequest('POST', endpoints.powerbi.workspace.delete, id);
			},
			associated_clients(id) {
				return apiRequest('POST', endpoints.powerbi.workspace.associated_clients, id);
			},
		},
		bis: {
			list() {
				return apiRequest('POST', endpoints.powerbi.bis.list);
			},
			show(id) {
				return apiRequest('POST', endpoints.powerbi.bis.show, id);
			},
			upsert(id) {
				return apiRequest('POST', endpoints.powerbi.bis.upsert, id);
			},
			delete(id) {
				return apiRequest('POST', endpoints.powerbi.bis.delete, id);
			},
			associated_profiles(id) {
				return apiRequest('POST', endpoints.powerbi.bis.associated_profiles, id);
			},
			render(id) {
				return apiRequest('POST', endpoints.powerbi.bis.render, id);
			},
			pages(id) {
				return apiRequest('POST', endpoints.powerbi.bis.pages, id);
			},
			page(data) {
				return apiRequest('POST', endpoints.powerbi.bis.page, data);
			},
			createImage(data) {
				return apiRequest('POST', endpoints.powerbi.bis.createImage, data);
			},
			destroyImage(data) {
				return apiRequest('POST', endpoints.powerbi.bis.destroyImage, data);
			},
			bookmark(id) {
				return apiRequest('POST', endpoints.powerbi.bis.bookmark, id);
			},
		},
	},
	repository: {
		upload(params) {
			return apiRequest('POST', endpoints.repository.upload, params);
		},
		rename(data) {
			return apiRequest('POST', endpoints.repository.rename, data);
		},
		destroy(id) {
			return apiRequest('POST', endpoints.repository.destroy, id);
		},
		view(id) {
			return apiRequest('POST', endpoints.repository.view, id, 'blob');
		},
	},
	signage: {
		show(id) {
			return apiRequest('POST', endpoints.signage.show, id);
		},
		upsert(data) {
			return apiRequest('POST', endpoints.signage.upsert, data);
		},
		delete(id) {
			return apiRequest('POST', endpoints.signage.delete, id);
		},
		addToBroadcast(data) {
			return apiRequest('POST', endpoints.signage.addToBroadcast, data);
		},
		slides(id) {
			return apiRequest('POST', endpoints.signage.slides, id);
		},
		moveSlideUp(id) {
			return apiRequest('POST', endpoints.signage.moveSlideUp, id);
		},
		moveSlideDown(id) {
			return apiRequest('POST', endpoints.signage.moveSlideDown, id);
		},
		deleteSlide(id) {
			return apiRequest('POST', endpoints.signage.deleteSlide, id);
		},
		setSlideTime(data) {
			return apiRequest('POST', endpoints.signage.setSlideTime, data);
		},
	},
};

export default apiService;
