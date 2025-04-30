const endpoints = {
	auth: {
		requestTokens: '/api/auth/request-tokens',
		revokeTokens: '/api/auth/revoke-tokens',
	},
	password: {
		reset: '/api/password/reset',
		requestReset: '/api/password/reset-request',
	},
	user: {
		requestEnviroment: 'api/user/request-enviroment',
		updateScope: '/api/user/update-scope',
	},
	account: {
		show: 'api/account/show',
	},
};

export default endpoints;
