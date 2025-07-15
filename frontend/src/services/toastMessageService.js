import 'vue3-toastify/dist/index.css';
import { toast } from 'vue3-toastify';
import { i18n } from '@/plugins/i18n';

const defaultToastOptions = {
	autoClose: 5000,
	type: 'success',
	theme: 'colored',
	transition: 'slide',
	position: 'bottom-right',
};

export function showToast(message, options = {}) {
	const finalOptions = { ...defaultToastOptions, ...options };
	const translatedMessage = i18n.global.t(message);
	toast(translatedMessage, finalOptions);
}
