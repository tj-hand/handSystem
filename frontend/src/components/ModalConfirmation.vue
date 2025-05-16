<template>
	<transition name="modal-fade">
		<div
			v-if="isVisible"
			@click.self="cancel"
			class="modal-overlay"
		>
			<div class="modal-container">
				<div class="modal-header">
					<h3 :class="{ danger: isDanger }">{{ title }}</h3>
					<div
						class="close-button"
						@click="cancel"
					>
						<div class="icon">close</div>
					</div>
				</div>
				<div class="modal-content">
					<slot>{{ message }}</slot>
				</div>
				<div class="modal-actions">
					<button
						@click="cancel"
						v-if="cancelButton"
						class="button neutral"
					>
						{{ cancelButton }}
					</button>
					<button
						class="button"
						@click="confirm"
						v-if="confirmButton"
						:class="isDanger ? 'danger' : 'primary'"
					>
						{{ confirmButton }}
					</button>
				</div>
			</div>
		</div>
	</transition>
</template>

<script>
import { defineComponent, ref, watch } from 'vue';

export default defineComponent({
	name: 'ModalConfirmation',
	props: {
		isDanger: { type: Boolean, default: false },
		modelValue: { type: Boolean, default: false },
		title: { type: String, default: 'Confirmation' },
		cancelButton: { type: String, default: 'Cancel' },
		confirmButton: { type: String, default: 'Confirm' },
		message: { type: String, default: 'Are you sure you want to perform this action?' },
	},
	emits: ['update:modelValue', 'confirm', 'cancel'],
	setup(props, { emit }) {
		const isVisible = ref(props.modelValue);

		const confirm = () => {
			emit('confirm');
			emit('update:modelValue', false);
		};

		const cancel = () => {
			emit('cancel');
			emit('update:modelValue', false);
		};

		watch(
			() => props.modelValue,
			(newValue) => {
				isVisible.value = newValue;
			}
		);
		const handleKeydown = (event) => {
			if (isVisible.value && event.key === 'Escape') {
				cancel();
			}
		};

		if (typeof window !== 'undefined') {
			window.addEventListener('keydown', handleKeydown);
		}

		return {
			isVisible,
			cancel,
			confirm,
		};
	},
	beforeUnmount() {
		if (typeof window !== 'undefined') {
			window.removeEventListener('keydown', this.handleKeydown);
		}
	},
});
</script>

<style scoped lang="scss">
.modal-overlay {
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 100;
	display: flex;
	position: fixed;
	align-items: center;
	justify-content: center;
	background-color: rgba(0, 0, 0, 0.5);
}

.modal-container {
	width: 100%;
	max-width: 500px;
	max-height: 90vh;
	overflow-y: auto;
	background: #ffffff;
	border-radius: 4px;
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.modal-header {
	display: flex;
	padding: 1rem;
	align-items: center;
	justify-content: space-between;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);

	h3 {
		margin: 0;
		color: $primary-color;
		font-size: 1rem * $phi-up;

		&.danger {
			color: $danger-color;
		}
	}

	.close-button {
		cursor: pointer;
		font-size: 1rem * $phi-up;
		color: rgba(0, 0, 0, 0.5);

		&:hover {
			color: $text-color;
		}
	}
}

.modal-content {
	font-size: 1rem;
	color: $text-color;
	padding: (1rem * $phi-up) 1rem;
}

.modal-actions {
	gap: 1rem;
	display: flex;
	padding: 1rem;
	justify-content: flex-end;
	border-top: 1px solid rgba(0, 0, 0, 0.1);

	.button {
		padding: (1rem * $phi) (1rem * $phi-up);
	}
}

// Transition effects
.modal-fade-enter-active,
.modal-fade-leave-active {
	transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
	opacity: 0;
}
</style>
