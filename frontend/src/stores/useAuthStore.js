import { ref } from 'vue';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore(
	'auth',
	() => {
		const enviroment = ref([]);
		const isAuthenticated = ref(false);

		function setAuthenticated(status) {
			isAuthenticated.value = status;
		}

		function setEnviroment(data) {
			enviroment.value = data;
		}

		function clearEnviroment() {
			enviroment.value = [];
		}

		return {
			enviroment,
			isAuthenticated,
			setEnviroment,
			clearEnviroment,
			setAuthenticated,
		};
	},
	{
		persist: true,
	}
);
