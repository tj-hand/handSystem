<template>
	<div
		class="scope-selector"
		v-if="hasMultipleAccounts || selectedAccountHasMultipleClients"
	>
		<div class="slide-wrapper">
			<div
				class="content-panel"
				:style="'width: ' + menuWidth + 'px;'"
			>
				<div
					class="selector-wrapper"
					v-if="hasMultipleAccounts"
					@click.stop="selector('account')"
				>
					<div class="selector-value">{{ clipText(accountName, 15) }}</div>
					<div class="icon">keyboard_arrow_down</div>
				</div>
				<div
					class="selector-wrapper"
					@click.stop="selector('client')"
					v-if="selectedAccountHasMultipleClients"
				>
					<div class="selector-value">{{ clipText(clientName, 15) }}</div>
					<div class="icon">keyboard_arrow_down</div>
				</div>
			</div>
			<div
				v-if="canAuthorize"
				class="authorizations"
			>
				<div
					class="icon"
					@click="toogleDrawer"
				>
					done_outline
				</div>
			</div>
		</div>
		<ItemSelector
			v-if="showSelector"
			class="item-selector"
			:menuWidth="menuWidth"
			@close="closeSelector"
			:selectorType="selectorType"
			:style="'width: calc(' + menuWidth + 'px - 2px);'"
		/>
		<DrawerContainer />
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { clipText } from '@/tools/clipText';
import ItemSelector from './ItemSelector.vue';
import { closest } from '@/tools/harmonize.js';
import { useUIStore } from '@/stores/useUIStore';
import { useDrawer } from '@/composables/useDrawer';
import { useAuthStore } from '@/stores/useAuthStore';
import authorizationQueue from './authorizationQueue.vue';
import DrawerContainer from '@/components/DrawerContainer.vue';

export default defineComponent({
	name: 'ScopeSelectorIndex',
	components: {
		ItemSelector,
		DrawerContainer,
	},
	setup() {
		const selectorType = ref('');
		const uiStore = useUIStore();
		const showSelector = ref(false);
		const authStore = useAuthStore();
		const { openDrawer } = useDrawer();

		const canAuthorize = computed(() => {
			return authStore.enviroment?.permissions?.SpecialPermissions?.some(
				(perm) => perm.identifier === 'auth.authorization_queue.relationships'
			);
		});

		const hasMultipleAccounts = computed(() => authStore.enviroment.scopes.length >= 2);

		const selectedAccountHasMultipleClients = computed(() => {
			const selected = authStore.enviroment.scopes.find(
				(scope) => scope.id === authStore.enviroment.current_scope.account_id
			);
			return selected?.clients?.length > 1 || false;
		});

		const accountName = computed(() => {
			return authStore.enviroment.current_scope?.account_name;
		});

		const clientName = computed(() => {
			return authStore.enviroment.current_scope?.client_name;
		});

		const toogleMenu = () => {
			uiStore.toggleScopeSelector();
		};

		const toogleDrawer = () => {
			openDrawer(authorizationQueue, closest(window.innerWidth, 600));
		};

		const menuWidth = computed(() => {
			return closest(window.innerWidth, 300);
		});

		const selector = (typeSelected) => {
			selectorType.value = typeSelected;
			showSelector.value = !showSelector.value;
		};

		const closeSelector = () => {
			showSelector.value = false;
		};

		return {
			clipText,
			menuWidth,
			clientName,
			accountName,
			selectorType,
			showSelector,
			canAuthorize,
			hasMultipleAccounts,
			selectedAccountHasMultipleClients,
			selector,
			toogleMenu,
			toogleDrawer,
			closeSelector,
		};
	},
});
</script>

<style lang="scss" scoped>
.scope-selector {
	padding: 0 1rem;
	position: relative;
	background-color: rgba($primary-color, 0.15);
	border-left: 1px solid rgba(255, 255, 255, 0.35);
	.slide-wrapper {
		display: flex;
		color: #ffffff;
		position: relative;
		flex-direction: row;
		justify-content: space-between;
		.content-panel {
			gap: 0;
			display: flex;
			flex-direction: row;
			align-items: center;

			.selector-wrapper {
				flex-grow: 1;
				display: flex;
				flex-basis: 0;
				max-width: 50%;
				cursor: pointer;
				align-items: center;
				flex-direction: row;
				padding: 2px (1rem * $phi);
				background-color: rgba($primary-color, 0.5);
				border-right: 1px solid rgba(255, 255, 255, 0.35);

				&:first-child {
					border-left: 1px solid rgba(255, 255, 255, 0.35);
				}

				.selector-value {
					flex-grow: 1;
					overflow: hidden;
					white-space: nowrap;
					text-overflow: ellipsis;
					font-size: 1rem * $phi-sr;
				}
			}
		}
		.authorizations {
			padding: 0 1rem;
			cursor: pointer;
			padding-top: 4px;
			line-height: 1rem;
			font-size: 1rem * $phi-up;
			background-color: rgba($success-color, 0.38);
		}
	}
	.item-selector {
		left: 0;
		top: 100%;
		position: absolute;
	}
}
</style>
