import { createI18n } from 'vue-i18n';

const i18n = createI18n({
	locale: import.meta.env.VITE_DEFAULT_LOCALE || navigator.language,
	fallbackLocale: 'pt',
	legacy: false,
	messages: {},
});

async function loadLocaleMessages(locale) {
	try {
		const response = await fetch(`/locales/${locale}.json`);
		const messages = await response.json();
		i18n.global.setLocaleMessage(locale, messages);
		i18n.global.locale.value = locale;
	} catch (error) {
		console.error(`Failed to load ${locale} translations`, error);
	}
}

export async function setupI18n(app) {
	await loadLocaleMessages(import.meta.env.VITE_DEFAULT_LOCALE || 'pt');
	app.use(i18n);
}

export { i18n };
