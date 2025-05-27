<template>
	<div class="authorization-queue">
		<PageTitle
			class="title"
			title="auth.authorization_queue.title"
		/>
		<div class="lists">
			<div
				class="list-wrapper"
				v-if="Object.values(list).some((group) => group?.length)"
			>
				<div
					:key="title"
					v-for="(group, title) in list"
				>
					<div
						class="group-title"
						v-if="group?.length"
					>
						{{ $t('auth.authorization_queue.group.' + title) }}
					</div>
					<div class="group-wrapper">
						<div
							class="item"
							v-for="(item, index) in group"
							@click="toogleAuthorization(item.id)"
						>
							<input
								type="checkbox"
								value="{{ item.id }}"
								:checked="isSelected(item.id)"
							/>
							<div class="profile">{{ item.parent }}</div>
							<div class="icon">key</div>
							<div class="user">{{ item.translate ? $t(item.child) : item.child }}</div>
						</div>
					</div>
				</div>
			</div>
			<div
				v-else
				class="no-records"
			>
				{{ $t('auth.authorization_queue.no_records') }}
			</div>
		</div>
		<div class="footer">
			<div class="left">
				<div
					@click="closeDrawer"
					class="button neutral"
					:class="{ 'only-icon': is_mobile() }"
				>
					<template v-if="is_mobile()">
						<span class="icon">close</span>
					</template>
					<template v-else-if="Object.values(list).some((group) => group?.length)">
						{{ $t('generic.cancel') }}
					</template>
					<template v-else>
						{{ $t('generic.close') }}
					</template>
				</div>
				<div
					@click="refuse()"
					class="button warning"
					:class="{ 'only-icon': is_mobile() }"
				>
					<template v-if="is_mobile()">
						<span class="icon">delete</span>
					</template>
					<template v-else>
						{{ $t('auth.authorization_queue.refuse_selected') }}
					</template>
				</div>
			</div>
			<div class="right">
				<div
					class="button primary"
					@click="approve('selected')"
					:class="{ 'only-icon': is_mobile() }"
				>
					<template v-if="is_mobile()">
						<span class="icon">check</span>
					</template>
					<template v-else>
						{{ $t('auth.authorization_queue.approve_selected') }}
					</template>
				</div>
				<div
					class="button primary"
					@click="approve('all')"
					:class="{ 'only-icon': is_mobile() }"
				>
					<template v-if="is_mobile()">
						<span class="icon">done_all</span>
					</template>
					<template v-else>
						{{ $t('auth.authorization_queue.approve_all') }}
					</template>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { onMounted } from 'vue';
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import apiService from '@/api/apiService';
import { is_mobile } from '@/tools/screenSizes';
import PageTitle from '@/components/PageTitle.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import { showToast } from '@/services/toastMessageService';

export default defineComponent({
	name: 'AuthorizationQueue',
	emits: ['close'],
	components: {
		PageTitle,
	},
	setup(props, { emit }) {
		const list = ref([]);
		const selectedIds = ref([]);
		const authStore = useAuthStore();

		const canAuthorize = computed(() => {
			return authStore.enviroment?.permissions?.SpecialPermissions?.some(
				(perm) => perm.identifier === 'auth.authorization_queue.relationships'
			);
		});

		const getAuthorizationQueue = async () => {
			if (canAuthorize.value) {
				const { success, queue } = await apiService.authorization.queue();
				if (success) list.value = queue;
			}
		};

		const toogleAuthorization = (id) => {
			const index = selectedIds.value.indexOf(id);
			index === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(index, 1);
		};

		const isSelected = (id) => {
			return selectedIds.value.includes(id);
		};

		const closeDrawer = () => {
			emit('close');
		};

		const approve = (records) => {
			if (records === 'all') selectAll();
			setAuthorizations('approve');
		};

		const refuse = () => {
			setAuthorizations('refuse');
		};

		const selectAll = () => {
			selectedIds.value = Object.values(list.value)
				.filter(Array.isArray)
				.flat()
				.map((item) => item.id);
			console.log(selectedIds.value);
		};

		const setAuthorizations = async (operationType) => {
			if (!selectedIds.value.length) return;
			const { success, message } = await apiService.authorization.set({
				ids: selectedIds.value,
				operation_type: operationType,
			});
			showToast(message, { type: success ? 'success' : 'error' });
			if (success) getAuthorizationQueue();
		};

		onMounted(() => {
			getAuthorizationQueue();
		});

		return {
			list,
			refuse,
			approve,
			is_mobile,
			isSelected,
			closeDrawer,
			toogleAuthorization,
		};
	},
});
</script>

<style lang="scss" scoped>
.authorization-queue {
	height: 100%;
	display: flex;
	flex-direction: column;
	.title {
		flex-grow: 0;
		padding-bottom: 1rem;
	}
	.lists {
		flex-grow: 1;

		.list-wrapper {
			padding: 1em;
			display: flex;
			color: $text-color;
			margin-bottom: 1rem;
			flex-direction: column;

			.group-title {
				font-weight: bold;
				padding-left: 1rem;
				color: $primary-color;
				font-size: 1rem * $phi-sr;
			}

			.group-wrapper {
				border-radius: 4px;
				margin: (1rem * $phi-down) 0 1rem 0;
				background-color: rgba(255, 255, 255, 0.75);

				.item {
					width: 100%;
					display: flex;
					cursor: pointer;
					gap: 1rem * $phi;
					flex-direction: row;
					align-items: center;
					padding: (1rem * $phi-down) (1rem * $phi);
					border-bottom: 1px solid rgba(0, 0, 0, 0.1);

					&:hover {
						background-color: rgba(0, 0, 0, 0.05);
					}

					.icon {
						line-height: 1em;
						color: $secondary-color;
					}

					&:last-child {
						border-bottom: 0;
					}

					input[type='checkbox'] {
						pointer-events: none;
					}
				}
			}
		}

		.no-records {
			height: 100%;
			flex-grow: 1;
			display: flex;
			font-weight: 100;
			color: $text-color;
			align-items: center;
			justify-content: center;
			font-size: 1rem * $phi-up;
		}
	}
	.footer {
		flex-grow: 0;
		display: flex;
		padding: 1rem;
		flex-direction: row;
		justify-content: space-between;
		border-top: 1px solid rgba(0, 0, 0, 0.1);
		.left,
		.right {
			gap: 1rem;
			display: flex;
			flex-direction: row;
			.button {
				padding: (1rem * $phi) 1rem;
				&.only-icon {
					padding: (1rem * $phi) 1rem;
					.icon {
						margin-right: 0;
					}
				}
			}
		}
	}
}
</style>
