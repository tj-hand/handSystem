<template>
	<div class="modules">
		<div
			:key="index"
			class="module"
			@click="goToModule(item.link_to)"
			v-for="(item, index) in modules"
		>
			<div class="icon">{{ item.icon }}</div>
			<div
				class="label"
				v-if="expandedMenu"
			>
				{{ $t(item.identifier) }}
			</div>
		</div>
	</div>
</template>

<script>
import { computed } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import { mobile } from '@/tools/screenSizes';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'Modules',
	props: {
		group: {
			type: String,
			required: true,
		},
	},
	setup(props) {
		const router = useRouter();
		const uiStore = useUIStore();
		const authStore = useAuthStore();

		const expandedMenu = computed(() => {
			return uiStore.expandedMenu;
		});

		const modules = computed(() => {
			return (
				authStore.enviroment.permissions?.SidebarModules?.filter((module) => module.subgroup === props.group) ??
				[]
			);
		});

		const goToModule = (module) => {
			mobile() ? uiStore.setExpandedMenu(false) : null;
			router.push({ name: module });
		};

		return { modules, expandedMenu, goToModule };
	},
});
</script>

<style lang="scss" scoped>
.modules {
	width: 100%;
	display: flex;
	max-width: 100%;
	margin-bottom: 1em;
	align-items: center;
	flex-direction: column;

	.module {
		width: 100%;
		display: flex;
		cursor: pointer;
		color: #ffffff;
		flex-direction: row;
		align-items: center;
		justify-content: center;
		padding: (1rem * $phi) 0;
		font-size: 1rem * $phi-up;

		&:hover {
			background-color: rgba(255, 255, 255, 0.2);
		}
		.label {
			width: 0;
			overflow: hidden;
			white-space: nowrap;
		}
	}
}

.expanded {
	.modules {
		align-items: flex-start;
		.module {
			margin: 0;
			padding: (1rem * $phi-down) 1rem;
			.label {
				width: 100%;
				font-size: 1rem;
				margin-left: 1em;
				transition: width 0.5s ease 0.5s;
			}
		}
	}
}
</style>
