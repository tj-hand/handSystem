import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUIStore = defineStore('ui', () => {
	const login = ref(false);
	const loading = ref(false);
	const expandedMenu = ref(false);

	const isLogin = computed(() => login.value);
	const isLoading = computed(() => loading.value);

	function showSpinner() {
		loading.value = true;
	}

	function hideSpinner() {
		loading.value = false;
	}

	function toggleExpandedMenu() {
		expandedMenu.value = !expandedMenu.value;
	}

	function fromLogin() {
		login.value = true;
	}

	function exitLoginProcess() {
		login.value = false;
	}

	return {
		login,
		loading,
		expandedMenu,

		isLogin,
		isLoading,

		showSpinner,
		hideSpinner,
		toggleExpandedMenu,
		fromLogin,
		exitLoginProcess,
	};
});
