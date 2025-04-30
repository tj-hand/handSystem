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
				@click="updateItem(item.id)"
				v-for="(item, index) in filteredItems"
			>
				{{ item.name }}
			</div>
		</div>
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import { onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/useAuthStore';

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
		const search = ref(null);
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
			emit('close');
			if (
				(props.selectorType === 'account' && id === authStore.enviroment.current_scope.account_id) ||
				(props.selectorType === 'client' && id === authStore.enviroment.current_scope.client_id)
			)
				return;
			await apiService.user.updateScope({ selector: props.selectorType, id: id });
			window.location.reload();
		};

		onMounted(() => {
			document.addEventListener('click', handleClickOutside);
		});

		onBeforeUnmount(() => {
			document.removeEventListener('click', handleClickOutside);
		});

		return { search, accounts, itemSelector, filteredItems, updateItem };
	},
});
</script>

<style lang="scss" scoped>
.item-selector {
	border-style: solid;
	border-radius: 0 0 4px 4px;
	background-color: #ffffff;
	border-width: 0 1px 1px 1px;
	border-color: $secondary-color;
	.search-wrapper {
		padding: 1rem * $phi-down;
		background-color: rgba(0, 0, 0, 0.05);

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
		color: $text-color;
		font-size: 1rem * $phi;
		padding: (1rem * $phi) 1rem;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
		&:hover {
			background-color: rgba(0, 0, 0, 0.05);
		}
		&:last-child {
			border-bottom: 0;
		}
	}
}
</style>
