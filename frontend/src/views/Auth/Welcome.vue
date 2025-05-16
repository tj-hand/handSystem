<template>
	<div class="welcome">
		<PageTitle title="auth.welcome.title" />
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import welcome from '@/pagebuilder/welcome.json';
import PageTitle from '@/components/PageTitle.vue';

export default defineComponent({
	name: 'Welcome',
	components: {
		PageTitle,
	},
	setup() {
		const record = [];
		return { welcome, record };
	},
});
</script>

<style scoped lang="scss">
.welcome {
	width: 100%;
	box-sizing: border-box;
}
</style>
