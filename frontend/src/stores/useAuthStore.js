import { ref } from 'vue';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore(
	'auth',
	() => {
		const profileData = ref([]);
		const isAuthenticated = ref(false);

		function setAuthenticated(status) {
			isAuthenticated.value = status;
		}

		function setProfile(data) {
			profileData.value = data;
		}

		function clearProfile() {
			profileData.value = [];
		}

		return {
			profileData,
			isAuthenticated,
			setProfile,
			clearProfile,
			setAuthenticated,
		};
	},
	{
		persist: true,
	}
);
