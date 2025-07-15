import { useAuthStore } from '@/stores/useAuthStore.js';
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
	{
		path: '/',
		component: () => import('@/layouts/Public/index.vue'),
		children: [
			{
				path: '',
				redirect: '/login',
			},
			{
				path: 'login',
				name: 'Login',
				component: () => import('@/views/Public/Login.vue'),
			},
			{
				path: 'forgot-password',
				name: 'ForgotPassword',
				component: () => import('@/views/Public/ForgotPassword.vue'),
			},
			{
				path: 'reset-password/:token?',
				name: 'ResetPassword',
				component: () => import('@/views/Public/ResetPassword.vue'),
			},
		],
	},
	{
		path: '/auth',
		component: () => import('@/layouts/Auth/index.vue'),
		children: [
			{
				path: 'welcome',
				name: 'Welcome',
				component: () => import('@/views/Auth/Welcome.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'users/:id?',
				name: 'LocalUsers',
				component: () => import('@/views/Auth/LocalUsers.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'profiles/:id?',
				name: 'Profiles',
				component: () => import('@/views/Auth/Profiles.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'bis/:id?',
				name: 'Bis',
				component: () => import('@/views/Auth/BIs.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'repository',
				name: 'Repository',
				component: () => import('@/views/Auth/Repository.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'signage/:id?',
				name: 'Signage',
				component: () => import('@/views/Auth/Signage.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'logs',
				name: 'Logs',
				component: () => import('@/views/Auth/Logs.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'logout',
				name: 'Logout',
				component: () => import('@/views/Auth/Logout.vue'),
				meta: { requiresAuthentication: true },
			},
			{
				path: 'settings',
				name: 'Settings',
				component: () => import('@/views/Auth/Settings.vue'),
				meta: { requiresAuthentication: true },
				children: [
					{
						path: 'account',
						name: 'Account',
						component: () => import('@/views/Auth/Account.vue'),
						meta: { requiresAuthentication: true },
					},
					{
						path: 'account/new',
						name: 'NewAccount',
						component: () => import('@/views/Auth/NewAccount.vue'),
						meta: { requiresAuthentication: true },
					},
					{
						path: 'clients/:id?',
						name: 'Clients',
						component: () => import('@/views/Auth/Clients.vue'),
						meta: { requiresAuthentication: true },
					},
					{
						path: 'groups/:id?',
						name: 'Groups',
						component: () => import('@/views/Auth/Groups.vue'),
						meta: { requiresAuthentication: true },
					},
					{
						path: 'users/:id?',
						name: 'Users',
						component: () => import('@/views/Auth/Users.vue'),
						meta: { requiresAuthentication: true },
					},
					{
						path: 'workspaces/:id?',
						name: 'Workspaces',
						component: () => import('@/views/Auth/Workspaces.vue'),
						meta: { requiresAuthentication: true },
					},
				],
			},
			{
				path: 'autheticated-user',
				name: 'authenticatedUser',
				component: () => import('@/views/Auth/AuthenticatedUser.vue'),
				meta: { requiresAuthentication: true },
			},
		],
	},
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to, from, next) => {
	document.title = to.meta.title || 'HandBi';
	requireAuth(to, from, next);
});

const requireAuth = (to, from, next) => {
	const authStore = useAuthStore();
	if (to.meta.requiresAuthentication && !authStore.isAuthenticated) {
		next({ path: '/login', query: { redirect: to.fullPath } });
	} else {
		next();
	}
};

export function setupRouter(app) {
	app.use(router);
}

export default router;
