import { ref, watch } from 'vue';
import { isUUID } from '@/tools/isUUID';
import { useRoute, useRouter } from 'vue-router';
import { onMounted, onBeforeUnmount } from 'vue';
import { showToast } from '@/services/toastMessageService';
import { formGuardService } from '@/services/formGuardService';

export function useRecordManagement(options) {
	const { apiService, getListFn, defaultRecord = {}, scope = 'account' } = options;

	const route = useRoute();
	const router = useRouter();
	const recordIsLoaded = ref(false);
	const record = formGuardService.getrecord();

	const updateRecord = (newValue) => {
		record.value = { ...newValue };
	};

	const updateRouteParams = async (newParams) => {
		const failed = await router.push({
			name: route.name,
			params: newParams,
		});
		return failed === false;
	};

	const cancelAction = () => {
		recordIsLoaded.value = false;
		updateRouteParams({ ...route.params, id: null });
	};

	const getRecord = async () => {
		recordIsLoaded.value = false;
		const hasValidId = isUUID(route.params.id);

		if (!hasValidId && route.params.id !== 'new') return;

		let success = route.params.id === 'new';
		let recordData = { ...defaultRecord };

		if (hasValidId) {
			const { success: apiSuccess, [options.singularName]: result } = await apiService.show({
				id: route.params.id,
			});

			success = apiSuccess;
			recordData = success ? result.record ?? { ...defaultRecord } : { ...defaultRecord };
		}

		formGuardService.setOriginal(recordData);
		recordIsLoaded.value = true;
		if (!success) cancelAction();
	};

	const mainAction = async (formGeneratorRef) => {
		if (!formGuardService.isDirty()) return;

		const validationResult = formGeneratorRef.value.validate();
		if (!validationResult) return;

		const {
			success,
			message,
			[options.singularName]: result,
		} = await apiService.upsert({
			record: record.value,
			scope,
		});

		if (success) {
			formGuardService.setOriginal(result.record);
			if (route.params.id === 'new') {
				await updateRouteParams({ ...route.params, id: result.record.id });
			}
			showToast(message);
			if (getListFn) getListFn();
		} else {
			showToast(message, { type: 'error' });
		}
	};

	const deleteAction = async (getListFn) => {
		const { success, message } = await apiService.delete({ id: record.value.id });
		if (success) {
			cancelAction();
			formGuardService.setOriginal({});
			if (getListFn) getListFn();
			return;
		} else {
			showToast(message, { type: 'error' });
		}
	};

	// Set up lifecycle hooks
	const setupLifecycleHooks = (router, getListFn) => {
		watch(
			() => route.params.id,
			() => getRecord(),
			{ immediate: true }
		);

		onMounted(() => {
			if (getListFn) getListFn();
			getRecord();
			formGuardService.enableAllGuards(router);
		});

		onBeforeUnmount(() => {
			formGuardService.disableAllGuards();
		});
	};

	return {
		record,
		recordIsLoaded,
		updateRecord,
		updateRouteParams,
		cancelAction,
		getRecord,
		mainAction,
		deleteAction,
		setupLifecycleHooks,
	};
}
