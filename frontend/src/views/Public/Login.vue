<template>
	<div class="view-wrapper">
		<div class="header-wrapper">
			<div class="logo-wrapper">
				<Logo class="logo" />
			</div>
			<div class="title-wrapper">
				<div class="icon">person_raised_hand</div>
				<div class="title">
					<h1>{{ $t('public.login.welcome') }} HandBI</h1>
					<p class="subtitle">{{ $t('public.login.subtitle') }}</p>
				</div>
			</div>
		</div>
		<div class="form-wrapper">
			<PageBuilder
				:grow="false"
				formSize="max"
				:schema="formLogin"
				:formData="formData"
				ref="formGeneratorRef"
			/>
			<div
				@click="autheticate"
				class="button primary"
			>
				<span class="icon">login</span>
				{{ $t('public.login.label.access') }}
			</div>
		</div>
	</div>
	<div class="outside-option">
		<div @click="forgotPassword">
			<span class="button primary-link">{{ $t('public.login.label.forgotPassword') }}</span>
		</div>
	</div>
</template>
<script>
import { defineComponent } from 'vue';
import Logo from '@/components/Logo.vue';
import apiService from '@/api/apiService';
import { ref, computed, watch } from 'vue';
import formLogin from '@/pagebuilder/login.json';
import { useRoute, useRouter } from 'vue-router';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { showToast } from '@/services/toastMessageService';
import { getProfile } from '@/services/userProfileService';
import PageBuilder from '@/components/PageBuilder/index.vue';

export default defineComponent({
	name: 'Login',
	components: {
		Logo,
		PageBuilder,
	},
	setup() {
		const route = useRoute();
		const router = useRouter();
		const uiStore = useUIStore();
		const authStore = useAuthStore();
		const formGeneratorRef = ref(null);
		const formData = { email: '', password: '' };

		const errorMessage = computed(() => {
			return route.query.error;
		});

		const forgotPassword = () => {
			router.push({ name: 'ForgotPassword' });
		};

		const autheticate = async () => {
			const validationResult = formGeneratorRef.value.validate();
			if (validationResult) {
				const response = await apiService.auth.getTokens({
					username: formData.email,
					password: formData.password,
				});
				response.success ? await login() : showToast(response.message, { type: 'error', autoClose: false });
			}
		};

		const login = async () => {
			uiStore.fromLogin();
			const response = await getProfile(router);
			if (!response.success) {
				showToast(response.message, { type: 'error', autoClose: false });
				return;
			}
			router.push({ name: authStore.profileData.home_page });
		};

		watch(
			errorMessage,
			(newError) => {
				if (newError) showToast(`public.login.error.${newError}`, { type: 'error', autoClose: false });
			},
			{ immediate: true }
		);

		return { formData, formLogin, formGeneratorRef, autheticate, forgotPassword };
	},
});
</script>

<style lang="scss" scoped></style>
