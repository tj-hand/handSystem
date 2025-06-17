<template>
	<div
		v-if="is_superuser"
		class="content-wrapper"
	>
		<PageTitle title="generic.logs" />
		<div class="data-container">
			<div
				class="filters"
				:style="'width: ' + responsive() + 'px;'"
			>
				<div class="title">{{ $t('generic.filters') }}</div>
				<div class="filter-form">
					<PageBuilder
						:schema="logs"
						:record="record"
						@update:record="updateRecord"
					/>
					<div
						@click="filter"
						class="button primary"
					>
						{{ $t('generic.filter') }}
					</div>
				</div>
			</div>
			<div class="item-data">
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>{{ $t('auth.log.operation_id') }}</th>
								<th class="center">{{ $t('auth.log.result') }}</th>
								<th>{{ $t('auth.log.ip_address') }}</th>
								<th>{{ $t('auth.log.operation') }}</th>
								<th>{{ $t('auth.log.additional_information') }}</th>
								<th class="center">{{ $t('auth.log.user_id') }}</th>
								<th>{{ $t('auth.log.user_name') }}</th>
								<th>{{ $t('auth.log.user_email') }}</th>
								<th>{{ $t('auth.log.account_id') }}</th>
								<th>{{ $t('auth.log.account_name') }}</th>
								<th>{{ $t('auth.log.client_id') }}</th>
								<th>{{ $t('auth.log.client_name') }}</th>
								<th>{{ $t('auth.log.object_type') }}</th>
								<th>{{ $t('auth.log.object_name') }}</th>
								<th class="center">{{ $t('auth.log.operation_data') }}</th>
								<th class="center">{{ $t('auth.log.operation_time') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr
								:key="index"
								class="log-row"
								v-for="(item, index) in logData"
							>
								<td>{{ item.id }}</td>
								<td class="center">
									{{ item.success ? $t('generic.success') : $t('generic.error') }}
								</td>
								<td>{{ item.request_ip }}</td>
								<td>{{ item.log_message }}</td>
								<td>{{ item.additional_information }}</td>
								<td class="center">{{ item.user_id }}</td>
								<td>{{ item.user_name }}</td>
								<td>{{ item.user_email }}</td>
								<td>{{ item.account_id }}</td>
								<td>{{ item.account_name }}</td>
								<td>{{ item.client_id }}</td>
								<td>{{ item.client_name }}</td>
								<td>{{ item.object_type }}</td>
								<td>{{ item.object_name }}</td>
								<td class="center">{{ formatFullDate(item.created_at) }}</td>
								<td class="center">{{ formatTime(item.created_at) }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
// Import from Vue
import { onMounted } from 'vue';
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { useAuthStore } from '@/stores/useAuthStore';

// Import Components and Services
import apiService from '@/api/apiService';
import logs from '@/pagebuilder/logs.json';
import { closest } from '@/tools/harmonize.js';
import { is_mobile } from '@/tools/screenSizes';
import PageTitle from '@/components/PageTitle.vue';
import PageBuilder from '@/components/PageBuilder/index.vue';

// Import composables
import { useRecordManagement } from '@/composables';

export default defineComponent({
	name: 'Logs',
	components: {
		PageTitle,
		PageBuilder,
	},
	setup() {
		const logData = ref([]);
		const today = new Date();
		const authStore = useAuthStore();

		const is_superuser = authStore.enviroment.user.superuser;

		const formatDate = (date) => {
			return date.toLocaleDateString('pt-BR').split('/').join('/');
		};

		const formatTime = (datetime) => new Date(datetime).toLocaleTimeString();
		const formatFullDate = (datetime) => new Date(datetime).toLocaleDateString();

		const { record, updateRecord, getRecord } = useRecordManagement({
			defaultRecord: {
				start_date: formatDate(new Date(today.getFullYear(), today.getMonth(), 1)),
				end_date: formatDate(today),
			},
		});

		const menuWidth = computed(() => {
			return closest(window.innerWidth, 300);
		});

		const responsive = () => {
			return is_mobile() ? 'calc(100% - 2rem)' : menuWidth.value;
		};

		const filter = async () => {
			const { success, logs } = await apiService.log.getData(record.value);
			if (success) logData.value = logs;
		};

		onMounted(() => {
			getRecord(false, true);
		});

		return {
			logs,
			record,
			logData,
			is_superuser,
			formatFullDate,
			formatTime,
			filter,
			responsive,
			updateRecord,
		};
	},
});
</script>

<style lang="scss" scoped>
.content-wrapper {
	.data-container {
		flex-grow: 1;
		display: flex;
		overflow: hidden;
		flex-direction: row;
		.filters {
			width: 100%;
			height: 100%;
			flex-grow: 0;
			display: flex;
			flex-shrink: 0;
			border-radius: 4px;
			flex-direction: column;
			margin: 1rem 0 1rem 1rem;
			height: calc(100% - 2rem);
			border: 1px solid rgba(0, 0, 0, 0.1);
			background: rgba($primary-color, 0.03);
			.title {
				flex-grow: 0;
				font-size: 1rem;
				color: #ffffff;
				font-weight: bold;
				padding: 1rem * $phi;
				border-radius: 4px 4px 0 0;
				background: rgba($secondary-color, 0.75);
			}
			.filter-form {
				flex-grow: 1;
				display: flex;
				padding: 1rem;
				overflow-y: auto;
				flex-direction: column;

				.page-builder {
					height: auto;
					&.grow {
						flex: 1 1 auto;
					}
				}
			}
		}
		.item-data {
			flex-grow: 1;
			display: flex;
			padding: 1rem;
			overflow: hidden;
			flex-direction: column;

			.table-container {
				width: 100%;
				height: 100%;
				overflow-y: auto;
				border: 1px solid rgba(0, 0, 0, 0.1);
				background-color: rgba(255, 255, 255, 0.9);

				table {
					width: 100%;
					border-collapse: collapse;

					thead th {
						top: 0;
						z-index: 1;
						color: #ffffff;
						position: sticky;
						white-space: nowrap;
						font-size: 1rem * $phi-sr;
						background-color: $secondary-color;
					}

					tbody tr {
						cursor: pointer;
						transition: all 0.5s ease;
						&:hover {
							background-color: rgba(0, 0, 0, 0.1);
						}
					}
					th,
					td {
						text-align: left;
						color: $text-color;
						white-space: nowrap;
						padding: (1rem * $phi-down) 1rem;
						border-bottom: 1px solid rgba(0, 0, 0, 0.1);

						&.center {
							text-align: center;
						}
					}
				}
			}
		}
	}
}

::-webkit-scrollbar {
	height: 10px;
}
</style>
