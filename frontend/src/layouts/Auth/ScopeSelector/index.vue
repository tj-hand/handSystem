<template>
	<div class="scope-selector">
		<div class="slide-wrapper">
			<div
				class="content-panel"
				:style="'width: ' + menuWidth + 'px;'"
			>
				<div
					@click.stop="selector('account')"
					class="selector-wrapper"
				>
					<div class="selector-value">{{ clipText(accountName, 15) }}</div>
					<div class="icon">keyboard_arrow_down</div>
				</div>
				<div
					@click.stop="selector('client')"
					class="selector-wrapper"
				>
					<div class="selector-value">{{ clipText(clientName, 15) }}</div>
					<div class="icon">keyboard_arrow_down</div>
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
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { clipText } from '@/tools/clipText';
import ItemSelector from './ItemSelector.vue';
import { closest } from '@/tools/harmonize.js';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';

export default defineComponent({
	name: 'ScopeSelectorIndex',
	components: {
		ItemSelector,
	},
	setup() {
		const selectorType = ref('');
		const showSelector = ref(false);
		const authStore = useAuthStore();
		const uiStore = useUIStore();

		const accountName = computed(() => {
			return authStore.enviroment.current_scope?.account_name;
		});

		const clientName = computed(() => {
			return authStore.enviroment.current_scope?.client_name;
		});

		const toogleMenu = () => {
			uiStore.toggleScopeSelector();
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
			menuWidth,
			clientName,
			accountName,
			selectorType,
			showSelector,
			clipText,
			selector,
			toogleMenu,
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
	}
	.item-selector {
		left: 0;
		top: 100%;
		position: absolute;
	}
}
</style>
