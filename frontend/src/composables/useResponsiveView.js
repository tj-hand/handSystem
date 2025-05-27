import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { isUUID } from '@/tools/isUUID';
import { is_smallScreen } from '@/tools/screenSizes';

export function useResponsiveView(recordIsLoaded, record) {
	const route = useRoute();

	const isCreatingNew = computed(() => route.params.id === 'new');

	const isListVisible = computed(() => {
		if (!is_smallScreen()) return true;
		const isEditing = recordIsLoaded.value && isUUID(route.params.id);
		return !isCreatingNew.value && !isEditing;
	});

	const isNoRecordVisible = computed(() => {
		return !(
			is_smallScreen() ||
			isCreatingNew.value ||
			(recordIsLoaded.value && Object.keys(record.value).length > 0)
		);
	});

	const isObjectCardVisible = computed(() => {
		const hasLoadedExisting = recordIsLoaded.value && isUUID(route.params.id);
		return hasLoadedExisting || isCreatingNew.value;
	});

	return {
		isCreatingNew,
		isListVisible,
		isNoRecordVisible,
		isObjectCardVisible,
	};
}
