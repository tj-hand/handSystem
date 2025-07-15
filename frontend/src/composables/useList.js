import { ref } from 'vue';

export function useList(apiGetList, entityName) {
	const list = ref([]);

	const getList = async () => {
		const { success, [entityName]: items } = await apiGetList();
		if (success) list.value = items;
	};

	return {
		list,
		getList,
	};
}
