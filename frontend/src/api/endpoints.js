const endpoints = {
	auth: {
		revokeTokens: '/api/auth/revoke-tokens',
		requestTokens: '/api/auth/request-tokens',
	},
	password: {
		reset: '/api/password/reset',
		requestReset: '/api/password/reset-request',
	},
	user: {
		show: 'api/user/show',
		delete: 'api/user/delete',
		upsert: 'api/user/upsert',
		exists: 'api/user/exists',
		sendInvite: 'api/user/send-invite',
		updateScope: '/api/user/update-scope',
		addToAccount: 'api/user/add-to-account',
		requestEnviroment: 'api/user/request-enviroment',
		associated_with_clients: 'api/user/associated-with-clients',
		associated_with_groups: 'api/user/associated-with-groups',
		associated_with_actions: 'api/user/associated-with-actions',
	},
	account: {
		show: 'api/account/show',
		users: 'api/account/users',
		delete: 'api/account/delete',
		groups: 'api/account/groups',
		upsert: 'api/account/upsert',
		clients: 'api/account/clients',
	},
	group: {
		show: 'api/group/show',
		delete: 'api/group/delete',
		upsert: 'api/group/upsert',
		associated_users: 'api/group/associated_users',
		associated_actions: 'api/group/associated_actions',
	},
	client: {
		show: 'api/client/show',
		delete: 'api/client/delete',
		upsert: 'api/client/upsert',
		associated_users: 'api/client/associated_users',
	},
	authorization: {
		set: 'api/authorization/set',
		queue: 'api/authorization/queue',
	},
};

export default endpoints;
