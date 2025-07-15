import App from './App.vue';
import { createApp } from 'vue';
import { setupRouter } from '@/router';
import { setupI18n } from '@/plugins/i18n';
import { setupPinia } from '@/plugins/pinia';

const app = createApp(App);
setupI18n(app);
setupPinia(app);
setupRouter(app);
app.mount('#app');
