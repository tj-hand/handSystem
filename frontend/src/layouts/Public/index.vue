<template>
	<div class="layout-container">
		<div class="master-row">
			<div class="left-section">
				<div class="logo-wrapper">
					<Logo
						class="logo"
						version="inverse"
					/>
				</div>
			</div>
			<div class="right-section">
				<Spinner />
				<RouterView />
			</div>
		</div>
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import Logo from '@/components/Logo.vue';
import Spinner from '@/components/Spinner.vue';

export default defineComponent({
	name: 'publicLayout',
	components: {
		Logo,
		Spinner,
	},
	setup() {},
});
</script>

<style lang="scss">
@use '@/styles/public.scss';

.layout-container {
	width: 100%;
	display: flex;
	height: 100vh;

	.master-row {
		width: 100%;
		display: flex;
		height: 100vh;
		overflow: hidden;

		.left-section {
			background: linear-gradient(rgba($secondary-color, 0.9), rgba($secondary-color, 0.9)),
				url('@/assets/images/home-background.jpg');

			height: 100vh;
			overflow: hidden;
			position: relative;
			width: 100% * $phi;
			background-size: cover;
			box-sizing: border-box;
			background-position: center;
			background-repeat: no-repeat;

			.logo-wrapper {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 100% * $phi-down;
				height: 100vh * $phi-down;

				.logo {
					width: 100% * $phi;
				}
			}
		}

		.right-section {
			height: 100vh;
			display: flex;
			overflow-y: auto;
			color: $text-color;
			align-items: center;
			box-sizing: border-box;
			flex-direction: column;
			justify-content: start;
			width: 100% * $phi-down;
			background-color: $background-color;
			box-shadow: 5px 0 12px inset rgba(0, 0, 0, 0.4);

			.spinner-container {
				height: 100%;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}
	}
}

@media (max-width: 1280px) {
	.layout-container {
		.master-row {
			flex-direction: column;

			.left-section {
				display: none;
			}

			.right-section {
				width: 100%;
				box-shadow: none;
			}
		}
	}
}
</style>
