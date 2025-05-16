<template>
	<div
		class="view-wrapper"
		id="forgot-password"
	>
		<div class="header-wrapper">
			<div class="logo-wrapper">
				<Logo class="logo" />
			</div>
			<div class="title-wrapper">
				<div class="title">
					<h1>{{ $t('public.forgot_password.title') }}</h1>
				</div>
			</div>
		</div>
		<div class="form-wrapper">
			<PageBuilder
				:grow="false"
				formSize="max"
				:key="builderKey"
				:record="record"
				ref="formGeneratorRef"
				:schema="formForgotPassword"
			/>
			<div
				class="button primary"
				@click="requestPasswordReset"
			>
				{{ $t('public.forgot_password.label.request_password_reset') }}
			</div>
		</div>
	</div>
	<div class="outside-option">
		<div @click="backToLogin">
			<span class="button primary-link">
				{{ $t('public.forgot_password.label.back_to_login') }}
				<span class="icon pos">keyboard_return</span>
			</span>
		</div>
	</div>
</template>
<script>
import { ref } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import Logo from '@/components/Logo.vue';
import apiService from '@/api/apiService';
import { showToast } from '@/services/toastMessageService';
import PageBuilder from '@/components/PageBuilder/index.vue';
import formForgotPassword from '@/pagebuilder/forgotPassword.json';

export default defineComponent({
	name: 'ForgotPassword',
	components: {
		Logo,
		PageBuilder,
	},
	setup() {
		const builderKey = ref(0);
		const router = useRouter();
		const formGeneratorRef = ref(null);
		const record = { record: { email: '' } };

		const backToLogin = () => {
			router.push({ name: 'Login' });
		};

		const requestPasswordReset = async () => {
			const validationResult = formGeneratorRef.value.validate();
			if (validationResult) {
				const response = await apiService.password.requestReset({ email: record.email });
				if (!response.success) {
					showToast(response.message, { type: 'error', autoClose: false });
					return;
				}
				record.email = '';
				builderKey.value++;
				showToast(response.message, { autoClose: 5000 });
			}
		};

		return { builderKey, record, formGeneratorRef, formForgotPassword, backToLogin, requestPasswordReset };
	},
});
</script>

<style lang="scss" scoped>
.title {
	width: 100%;
	text-align: center;
}
</style>
