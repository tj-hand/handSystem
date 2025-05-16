// services/formGuardService.js
import { ref, toRaw } from 'vue';
import { i18n } from '@/plugins/i18n';
import { useUIStore } from '@/stores/useUIStore';

class FormGuardService {
	constructor() {
		this.router = null;
		this.record = ref({});
		this.unloadHandler = null;
		this.originalData = ref({});
		this.uiStore = useUIStore();
		this.guardRegistered = false;
		this.routerGuardActive = false;
	}

	setOriginal(data) {
		const clone = this.deepClone(data);
		this.record.value = { ...clone };
		this.originalData.value = clone;
		this.uiStore.setDirtyForm(false);
	}

	getrecord() {
		return this.record;
	}

	getPlainrecord() {
		return toRaw(this.record.value);
	}

	deepClone(data) {
		return JSON.parse(JSON.stringify(data));
	}

	isEqual(value1, value2) {
		const val1 = toRaw(value1);
		const val2 = toRaw(value2);

		if (val1 === val2) return true;
		if (val1 === null || val2 === null) return false;
		if (val1 === undefined || val2 === undefined) return false;

		// Check if both are arrays
		if (Array.isArray(val1) && Array.isArray(val2)) {
			if (val1.length !== val2.length) return false;

			// For arrays containing objects with IDs or UUIDs, sort them before comparison
			if (val1.length > 0 && typeof val1[0] === 'object') {
				// Find a key we can sort by (id, uuid, or first key available)
				const sortKey = val1[0].id || val1[0].uuid || Object.keys(val1[0])[0];

				// Clone and sort both arrays
				const sortedVal1 = [...val1].sort((a, b) =>
					a[sortKey] < b[sortKey] ? -1 : a[sortKey] > b[sortKey] ? 1 : 0
				);
				const sortedVal2 = [...val2].sort((a, b) =>
					a[sortKey] < b[sortKey] ? -1 : a[sortKey] > b[sortKey] ? 1 : 0
				);

				// Compare each item in the sorted arrays
				for (let i = 0; i < sortedVal1.length; i++) {
					if (!this.isEqual(sortedVal1[i], sortedVal2[i])) return false;
				}
				return true;
			}

			// For simple arrays, compare each element
			for (let i = 0; i < val1.length; i++) {
				if (!this.isEqual(val1[i], val2[i])) return false;
			}
			return true;
		}

		if (typeof val1 === 'object' && typeof val2 === 'object') {
			const keys1 = Object.keys(val1);
			const keys2 = Object.keys(val2);

			if (keys1.length !== keys2.length) return false;

			for (const key of keys1) {
				if (!keys2.includes(key)) return false;
				if (!this.isEqual(val1[key], val2[key])) return false;
			}
			return true;
		}

		return val1 === val2;
	}

	isDirty() {
		if (this.navigationInProgress) return false;
		const rawrecord = toRaw(this.record.value);
		const dirty = !this.isEqual(rawrecord, this.originalData.value);
		if (this.uiStore.isDirtyForm !== dirty) this.uiStore.setDirtyForm(dirty);
		return dirty;
	}

	activateUnloadProtection() {
		if (this.unloadHandler) return;

		this.unloadHandler = (e) => {
			if (this.routerGuardActive && this.isDirty()) {
				e.preventDefault();
				e.returnValue = '';
			}
		};

		window.addEventListener('beforeunload', this.unloadHandler);
	}

	deactivateUnloadProtection() {
		if (this.unloadHandler) {
			window.removeEventListener('beforeunload', this.unloadHandler);
			this.unloadHandler = null;
		}
	}

	registerRouterGuard(router) {
		if (this.guardRegistered) return;
		router.beforeEach((to, from, next) => {
			if (this.routerGuardActive && this.isDirty()) {
				const confirmLeave = window.confirm(i18n.global.t('generic.unsavedData'));
				if (!confirmLeave) return next(false);
			}
			this.uiStore.setDirtyForm(false);
			next();
		});
		this.guardRegistered = true;
	}

	enableAllGuards(router) {
		this.router = router;
		this.routerGuardActive = true;
		this.activateUnloadProtection();
		this.registerRouterGuard(router);
	}

	disableAllGuards() {
		this.routerGuardActive = false;
		this.deactivateUnloadProtection();
	}
}

export const formGuardService = new FormGuardService();
