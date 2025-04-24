const endpoints = {
	auth: {
		getTokens: '/api/auth/token',
		logout: '/api/auth/logout',
		login: '/api/user/login',
	},
	password: {
		reset: '/api/password/reset',
		requestReset: '/api/password/reset-request',
	},
};

export default endpoints;
