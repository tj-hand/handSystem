<template>
	<div class="footer">
		<div class="left-wrapper">
			<div
				v-if="deleteButton"
				@click="deleteAction"
				class="button warning"
				:class="{ 'only-icon': is_mobile() }"
			>
				<div class="icon">delete</div>
				<div v-if="!is_mobile()">{{ deleteButton }}</div>
			</div>
		</div>
		<div class="right-wrapper">
			<div
				v-if="cancelButton"
				@click="cancelAction"
				class="button neutral"
				:class="{ 'only-icon': is_mobile() }"
			>
				<div class="icon">close</div>
				<div v-if="!is_mobile()">{{ cancelButton }}</div>
			</div>
			<div
				v-if="actionButton"
				@click="actionAction"
				class="button primary"
				:class="{ 'only-icon': is_mobile() }"
			>
				<div class="icon">check</div>
				<div v-if="!is_mobile()">{{ actionButton }}</div>
			</div>
		</div>
	</div>
</template>

<script>
import { defineComponent } from 'vue';
import { is_mobile } from '@/tools/screenSizes';

export default defineComponent({
	name: 'Logo',
	props: {
		actionButton: { type: String },
		cancelButton: { type: String },
		deleteButton: { type: String },
	},
	emit: ['action', 'cancel', 'delete'],
	setup(props, { emit }) {
		const deleteAction = () => {
			emit('delete');
		};

		const cancelAction = () => {
			emit('cancel');
		};

		const actionAction = () => {
			emit('action');
		};

		return {
			is_mobile,
			deleteAction,
			cancelAction,
			actionAction,
		};
	},
});
</script>

<style lang="scss" scoped>
.footer {
	width: 100%;
	display: flex;
	flex-direction: row;
	align-items: center;
	box-sizing: border-box;
	background-color: #ffffff;
	padding: (1rem * $phi-sr) 1rem;
	justify-content: space-between;
	border-top: 1px solid rgba(0, 0, 0, 0.1);

	.left-wrapper {
		gap: 1rem;
		display: flex;
	}

	.right-wrapper {
		gap: 1rem;
		display: flex;
		justify-content: flex-end;
	}
}

.button {
	padding: (1rem * $phi) (1rem * $phi-up);
	&.warning {
	}
	&.neutral {
	}
	&.only-icon {
		padding: (1rem * $phi) 1rem;
		.icon {
			margin-right: 0;
		}
	}
}
</style>
