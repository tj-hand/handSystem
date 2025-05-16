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
		updateScope: '/api/user/update-scope',
		addToAccount: 'api/user/add-to-account',
		requestEnviroment: 'api/user/request-enviroment',
	},
	account: {
		show: 'api/account/show',
		users: 'api/account/users',
		upsert: 'api/account/upsert',
	},
};

export default endpoints;
