<template>
	<div
		ref="itemSelector"
		class="item-selector"
		:style="'width: ' + menuWidth + 'px;'"
	>
		<div class="search-wrapper">
			<div class="form-element-wrapper">
				<input
					type="text"
					v-model="search"
					:placeholder="$t('generic.search')"
				/>
				<div class="icon">search</div>
			</div>
		</div>
		<div class="items-wrapper">
			<div
				class="item"
				:key="index"
				@click="handleItemClick(item.id)"
				v-for="(item, index) in filteredItems"
			>
				{{ item.name }}
			</div>
		</div>
	</div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import { onMounted, onBeforeUnmount } from 'vue';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { formGuardService } from '@/services/formGuardService';

export default defineComponent({
	name: 'ItemSelector',
	props: {
		menuWidth: {
			type: Number,
			requiered: true,
		},
		selectorType: {
			type: String,
			requiered: true,
		},
	},
	emits: ['close'],
	setup(props, { emit }) {
		const { t } = useI18n();
		const search = ref(null);
		const uiStore = useUIStore();
		const itemSelector = ref(null);
		const authStore = useAuthStore();

		const accounts = computed(() =>
			authStore.enviroment.scopes.map((account) => ({
				id: account.id,
				name: account.name,
			}))
		);

		const clients = computed(() => {
			const account = authStore.enviroment.scopes.find(
				(acc) => acc.id === authStore.enviroment.current_scope.account_id
			);
			return account?.clients ?? [];
		});

		const items = computed(() => (props.selectorType === 'account' ? accounts.value : clients.value));

		const filteredItems = computed(() => {
			return items.value.filter((item) =>
				(item.name ?? '').toLowerCase().includes((search.value ?? '').toLowerCase())
			);
		});

		const handleClickOutside = (event) => {
			if (itemSelector.value && !itemSelector.value.contains(event.target)) emit('close');
		};

		const updateItem = async (id) => {
			if (
				(props.selectorType === 'account' && id === authStore.enviroment.current_scope.account_id) ||
				(props.selectorType === 'client' && id === authStore.enviroment.current_scope.client_id)
			) {
				emit('close');
				return;
			}

			emit('close');

			const response = await apiService.user.updateScope({ selector: props.selectorType, id: id });
			if (response.success) window.location.reload();
		};

		const handleItemClick = (id) => {
			if (uiStore.isDirtyForm) {
				const confirmNavigation = window.confirm(t('generic.unsavedData'));

				if (!confirmNavigation) {
					emit('close');
					return;
				}
				formGuardService.setOriginal({});
			}
			updateItem(id);
		};

		onMounted(() => {
			document.addEventListener('click', handleClickOutside);
		});

		onBeforeUnmount(() => {
			document.removeEventListener('click', handleClickOutside);
		});

		return { search, accounts, itemSelector, filteredItems, handleItemClick };
	},
});
</script>

<style lang="scss" scoped>
.item-selector {
	z-index: 10;
	height: 16rem;
	margin-left: 1rem;
	border-style: solid;
	border-color: #ffffff;
	border-radius: 0 0 4px 0;
	background-color: #cccccc;
	border-width: 2px 1px 1px 1px;
	box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
	.search-wrapper {
		padding: 1rem * $phi-down;
		background-color: rgba(0, 0, 0, 0.38);

		.form-element-wrapper {
			width: 100%;
			display: flex;
			align-items: center;
			border-radius: 4px;
			box-sizing: border-box;
			transition: all 0.5s ease;
			padding: 1rem * $phi-down;
			justify-content: space-between;
			background: $form-wrapper-backgorund-color;
			border: 1px solid $form-wrapper-border-color;
			box-shadow: 2px 2px 5px inset rgba(0, 0, 0, 0.05);
			.icon {
				font-size: 1.25rem;
				color: $text-color;
			}
			input {
				border: 0;
				outline: 0;
				flex-grow: 1;
				color: $text-color;
				background-color: transparent;
			}
		}
	}

	.item {
		cursor: pointer;
		font-weight: bold;
		color: #ffffff;
		font-size: 1rem * $phi-sr;
		padding: (1rem * $phi) 1rem;
		background-color: rgba(0, 0, 0, 0.2);
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
		&:hover {
			background-color: rgba(0, 0, 0, 0.25);
		}
	}
}
</style>
