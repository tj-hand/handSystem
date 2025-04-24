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
