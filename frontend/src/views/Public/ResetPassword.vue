<template>
	<div
		id="reset-password"
		class="view-wrapper"
	>
		<div class="header-wrapper">
			<div class="logo-wrapper">
				<Logo class="logo" />
			</div>
			<div class="title-wrapper">
				<div class="title">
					<h1>{{ $t('public.reset_password.title') }}</h1>
				</div>
			</div>
		</div>
		<div
			class="form-wrapper"
			v-if="formData.token"
		>
			<PageBuilder
				:grow="false"
				formSize="max"
				:key="builderKey"
				:formData="formData"
				ref="formGeneratorRef"
				:schema="formResetPassword"
			/>
			<div
				class="button primary"
				@click="resetPassword"
			>
				{{ $t('public.reset_password.label.reset_password') }}
			</div>
		</div>
		<div
			v-else
			class="form-wrapper"
		>
			<div class="error">
				{{ $t('public.reset_password.error.invalid_password_reset_token') }}
			</div>
		</div>
	</div>
	<div class="outside-option">
		<div @click="backToLogin">
			<span class="button primary-link">
				{{ $t('public.forgot_password.label.back_to_login') }}
				<span class="icon">keyboard_return</span>
			</span>
		</div>
	</div>
</template>
<script>
import { ref } from 'vue';
import { defineComponent } from 'vue';
import Logo from '@/components/Logo.vue';
import apiService from '@/api/apiService';
import { useRoute, useRouter } from 'vue-router';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import formResetPassword from '@/pagebuilder/resetPassword.json';

export default defineComponent({
	name: 'ResetPassword',
	components: {
		Logo,
		PageBuilder,
	},
	setup() {
		const route = useRoute();
		const builderKey = ref(0);
		const router = useRouter();
		const formGeneratorRef = ref(null);
		const token = ref(route.params.token);
		let formData = { email: '', password: '', password_confirmation: '', token: token.value };

		const backToLogin = () => {
			router.push({ name: 'Login' });
		};

		const resetPassword = async () => {
			const validationResult = formGeneratorRef.value.validate();
			formData.password_confirmation = formData.password;
			if (validationResult) {
				const response = await apiService.password.reset(formData);
				if (response.success) {
					formData = { email: '', password: '', password_confirmation: '', token: route.params.token };
					builderKey.value++;
					showToast(response.message, { autoClose: 5000 });
				} else {
					showToast(response.message, { type: 'error', autoClose: 5000 });
				}
			}
		};

		return { formData, builderKey, formGeneratorRef, formResetPassword, backToLogin, resetPassword };
	},
});
</script>

<style lang="scss" scoped>
.title {
	width: 100%;
	text-align: center;
}
</style>
