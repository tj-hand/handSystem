import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUIStore = defineStore(
	'ui',
	() => {
		const login = ref(false);
		const loading = ref(false);
		const expandedMenu = ref(false);
		const scopeSelector = ref(false);

		const isLogin = computed(() => login.value);
		const isLoading = computed(() => loading.value);

		function showSpinner() {
			loading.value = true;
		}

		function hideSpinner() {
			loading.value = false;
		}

		function setExpandedMenu(state) {
			expandedMenu.value = state;
		}

		function toggleExpandedMenu() {
			expandedMenu.value = !expandedMenu.value;
		}

		function setScopeSelector(state) {
			scopeSelector.value = state;
		}

		function toggleScopeSelector() {
			scopeSelector.value = !scopeSelector.value;
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
			scopeSelector,

			isLogin,
			isLoading,

			fromLogin,
			showSpinner,
			hideSpinner,
			setExpandedMenu,
			exitLoginProcess,
			setScopeSelector,
			toggleExpandedMenu,
			toggleScopeSelector,
		};
	},
	{
		persist: true,
	}
);
