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
				permissionRequired: {
					category: 'AccountAdmin',
				},
			},
			{
				label: 'generic.clients',
				link_to: 'Clients',
				permissionRequired: {
					category: 'Clients',
					identifier: 'auth.clients.module',
				},
			},
			{
				label: 'generic.groups',
				link_to: 'Groups',
				permissionRequired: {
					category: 'Groups',
					identifier: 'auth.groups.module',
				},
			},
			{
				label: 'generic.users',
				link_to: 'Users',
				permissionRequired: {
					category: 'AccountUsers',
					identifier: 'auth.account_users.module',
				},
			},
			{
				label: 'generic.custom.workspaces',
				link_to: 'Workspaces',
			},
			{
				label: 'auth.account.new',
				link_to: 'NewAccount',
				permissionRequired: {
					category: 'SuperUser',
				},
			},
		];

		return { items };
	},
});
</script>

<style scoped lang="scss"></style>
