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
	},
	account: {
		show: 'api/account/show',
		users: 'api/account/users',
		delete: 'api/account/delete',
		groups: 'api/account/groups',
		upsert: 'api/account/upsert',
	},
	group: {
		show: 'api/group/show',
		delete: 'api/group/delete',
		upsert: 'api/group/upsert',
		associated_users: 'api/group/associated_users',
		associated_actions: 'api/group/associated_actions',
	},
	authorization: {
		set: 'api/authorization/set',
		queue: 'api/authorization/queue',
	},
};

export default endpoints;
