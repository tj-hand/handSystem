<template>
	<div class="welcome">
		<PageBuilder
			:grow="true"
			:schema="welcome"
			:formData="formData"
			ref="formGeneratorRef"
		/>
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import welcome from '@/pagebuilder/welcome.json';
import PageBuilder from '@/components/PageBuilder/index.vue';

export default defineComponent({
	name: 'Welcome',
	components: {
		PageBuilder,
	},
	setup() {
		const formData = [];
		return { welcome, formData };
	},
});
</script>

<style scoped lang="scss">
.welcome {
	width: 100%;
	box-sizing: border-box;
}
</style>
