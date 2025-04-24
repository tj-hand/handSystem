import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';

export function setupPinia(app) {
	const pinia = createPinia();
	pinia.use(piniaPluginPersistedstate);
	app.use(pinia);
}
