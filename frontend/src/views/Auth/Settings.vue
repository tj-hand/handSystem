<template>
	<div class="settings">
		<PageBuilder
			:grow="true"
			:schema="settings"
			:formData="formData"
			ref="formGeneratorRef"
		/>
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import settings from '@/pagebuilder/settings.json';
import PageBuilder from '@/components/PageBuilder/index.vue';

export default defineComponent({
	name: 'Settings',
	components: {
		PageBuilder,
	},
	setup() {
		const formData = [];
		return { formData, settings };
	},
});
</script>

<style scoped lang="scss">
.settings {
	width: 100%;
	display: flex;
	box-sizing: border-box;
}
</style>
