<template>
	<div
		v-if="can('module')"
		class="content-wrapper"
	>
		<PageTitle title="auth.repository.title" />

		<div class="data-container">
			<FileManager permissions="Repository" />
		</div>
	</div>
</template>

<script>
// Import from Vue
import { defineComponent } from 'vue';

// Import Components and Services
import PageTitle from '@/components/PageTitle.vue';
import FileManager from '@/components/FileManager.vue';

// Import composables
import { usePermissions } from '@/composables';

export default defineComponent({
	name: 'Repository',
	components: {
		PageTitle,
		FileManager,
	},
	setup() {
		const { can } = usePermissions('Repository');
		return { can };
	},
});
</script>

<style lang="scss" scoped></style>
