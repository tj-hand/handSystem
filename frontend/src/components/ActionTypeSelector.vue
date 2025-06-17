<template>
	<div class="result-type-selector">
		<label class="label">{{ $t('generic.type') }}</label>
		<div class="button-group">
			<div
				class="btn btn-blue"
				:class="{ active: fieldValue === 'both' }"
				@click="setType('both')"
			>
				{{ $t('generic.both') }}
			</div>
			<div
				class="btn btn-green"
				:class="{ active: fieldValue === 'success' }"
				@click="setType('success')"
			>
				{{ $t('generic.success') }}
			</div>
			<div
				class="btn btn-red"
				:class="{ active: fieldValue === 'error' }"
				@click="setType('error')"
			>
				{{ $t('generic.error') }}
			</div>
		</div>
	</div>
</template>

<script>
import { ref, defineComponent } from 'vue';

export default defineComponent({
	name: 'ResultTypeSelector',
	props: {
		params: { type: Object, required: true },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const fieldValue = ref(props.record[props.params.db_name]);

		const setType = (value) => {
			fieldValue.value = value;
			emit('updateData', {
				fieldName: props.params.db_name,
				fieldValue: value,
			});
		};

		return {
			fieldValue,
			setType,
		};
	},
});
</script>

<style lang="scss" scoped>
.result-type-selector {
	margin-bottom: 1rem;
	.button-group {
		display: flex;
		overflow: hidden;
		border-radius: 4px;
	}

	.btn {
		flex: 1;
		cursor: pointer;
		user-select: none;
		text-align: center;
		font-size: 1rem * $phi-sr;
		padding: 1rem * $phi-down;
		margin-top: 1rem * $phi-down;
		transition: background-color 0.2s, color 0.2s, border-color 0.2s;

		// Outline by default
		border: 1px solid transparent;
		background-color: rgba(255, 255, 255, 0.62);

		&:not(:last-child) {
			border-right: 1px solid #ccc;
		}

		&:hover {
			filter: brightness(0.95);
		}

		&.active {
			color: #fff;
		}

		// Blue button (Both)
		&.btn-blue {
			border-right: 0;
			color: $primary-color;
			border-radius: 4px 0 0 4px;
			border-color: rgba($primary-color, 0.38);

			&.active {
				color: #ffffff;
				background-color: rgba($primary-color, 0.38);
			}
		}

		// Green button (Success)
		&.btn-green {
			border-left: 0;
			border-right: 0;
			color: $success-color;
			border-color: rgba($success-color, 0.38);

			&.active {
				color: #ffffff;
				background-color: rgba($success-color, 0.38);
			}
		}

		// Red button (Error)
		&.btn-red {
			border-left: 0;
			color: $danger-color;
			border-radius: 0 4px 4px 0;
			border-color: rgba($danger-color, 0.38);

			&.active {
				color: #ffffff;
				background-color: rgba($danger-color, 0.38);
			}
		}
	}
}
</style>
