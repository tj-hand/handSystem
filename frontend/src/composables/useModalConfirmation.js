import { ref } from 'vue';

export function useModalConfirmation() {
	const showModal = ref(false);
	const modalText = ref('');
	const modalTitle = ref('');
	const modalDanger = ref(false);
	const modalCancel = ref('');
	const modalConfirm = ref('');
	const modalMethod = ref(null);

	const showDeleteConfirmation = (deleteCallback) => {
		showModal.value = true;
		modalDanger.value = true;
		modalMethod.value = deleteCallback;
		modalCancel.value = 'generic.cancel';
		modalConfirm.value = 'generic.delete';
		modalTitle.value = 'generic.confirm_delete';
		modalText.value = 'generic.delete_confirmation';
	};

	const configureModal = (options) => {
		showModal.value = true;
		modalDanger.value = options.isDanger || false;
		modalMethod.value = options.callback;
		modalCancel.value = options.cancelText || 'generic.cancel';
		modalConfirm.value = options.confirmText || 'generic.confirm';
		modalTitle.value = options.title || '';
		modalText.value = options.message || '';
	};

	return {
		showModal,
		modalText,
		modalTitle,
		modalDanger,
		modalCancel,
		modalConfirm,
		modalMethod,
		configureModal,
		showDeleteConfirmation,
	};
}
