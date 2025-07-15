<template>
	<img
		class="logo"
		:src="logoSrc"
		:alt="altText"
	/>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import logo from '@/assets/images/logo.svg';
import icon from '@/assets/images/logo-icon.svg';
import inverseLogo from '@/assets/images/logo-inverse.svg';
import inverseIcon from '@/assets/images/logo-icon-inverse.svg';

export default defineComponent({
	name: 'Logo',
	props: {
		version: {
			type: String,
			default: 'default',
		},
		isIcon: {
			type: Boolean,
			default: false,
		},
	},
	setup(props) {
		const altText = computed(() => {
			return props.isIcon ? 'Logo Icon' : 'Logo';
		});

		const logoSrc = computed(() => {
			if (props.isIcon) return props.version === 'inverse' ? inverseIcon : icon;
			return props.version === 'inverse' ? inverseLogo : logo;
		});

		return {
			logoSrc,
			altText,
		};
	},
});
</script>

<style lang="scss" scoped></style>
