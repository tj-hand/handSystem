<template>
	<div class="scope-selector">
		<div class="slide-wrapper">
			<div
				@click="toogleMenu"
				class="icon-wrapper"
			>
				<div
					class="icon"
					v-if="isOpen"
				>
					cancel
				</div>
				<div
					class="icon"
					v-else
				>
					database
				</div>
			</div>
			<div
				class="content-panel"
				:class="{ 'is-open': isOpen }"
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

		const isOpen = computed(() => {
			return uiStore.scopeSelector;
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
			isOpen,
			menuWidth,
			accountName,
			clientName,
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
	top: 0;
	right: 0;
	z-index: 50;
	position: fixed;
	.slide-wrapper {
		display: flex;
		color: #ffffff;
		position: relative;
		flex-direction: row;
		border-radius: 0 0 0 6px;
		background-color: $primary-color;
		.icon-wrapper {
			padding: 1rem * $phi-down;
			border-right: 1px solid rgba(255, 255, 255, 0.35);
			.icon {
				cursor: pointer;
			}
		}
		.content-panel {
			gap: 0;
			opacity: 0;
			display: flex;
			flex-direction: row;
			align-items: center;
			transition: width 0.5s ease, opacity 0.3s ease 0.5s;

			&.is-open {
				opacity: 1;
				width: auto;
			}

			&:not(.is-open) {
				opacity: 0;
				width: 0 !important;
				transition: all 0.5s ease;
			}

			.selector-wrapper {
				flex-grow: 1;
				display: flex;
				flex-basis: 0;
				max-width: 50%;
				cursor: pointer;
				border-radius: 4px;
				align-items: center;
				flex-direction: row;
				transition: width 0.5s ease 0.5s;
				padding: 0 (1rem * $phi);
				border-right: 1px solid rgba(255, 255, 255, 0.35);

				.selector-value {
					flex-grow: 1;
					overflow: hidden;
					font-size: 0.618em;
					white-space: nowrap;
					text-overflow: ellipsis;
				}
			}
		}
	}
	.item-selector {
		right: 0;
		top: 100%;
		position: absolute;
	}
}
</style>
