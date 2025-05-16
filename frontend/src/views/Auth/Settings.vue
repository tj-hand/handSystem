<template>
	<div class="settings">
		<PageTitle title="auth.settings.title" />
		<HorizontalSubmenu :params="items" />
		<RouterView />
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import PageTitle from '@/components/PageTitle.vue';
import HorizontalSubmenu from '@/components/HorizontalSubmenu.vue';

export default defineComponent({
	name: 'Settings',
	components: {
		PageTitle,
		HorizontalSubmenu,
	},
	setup() {
		const items = [
			{
				label: 'generic.account',
				link_to: 'Account',
			},
			{
				label: 'generic.clients',
				link_to: 'Clients',
			},
			{
				label: 'generic.groups',
				link_to: 'Groups',
			},
			{
				label: 'generic.users',
				link_to: 'Users',
			},
			{
				label: 'generic.custom.workspaces',
				link_to: 'Workspaces',
			},
		];

		return { items };
	},
});
</script>

<style scoped lang="scss"></style>
