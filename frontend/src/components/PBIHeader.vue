<template>
	<div class="pbi-header">
		<div class="type">{{ record.microsoft_type }}</div>
		<div class="name">{{ record.microsoft_name }}</div>
		<div class="id">{{ record.microsoft_id }}</div>
	</div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'PBIHeader',
	props: {
		params: { type: Object },
		formSize: { type: String, default: '' },
		record: { type: Object, required: true },
	},
	setup() {},
});
</script>

<style lang="scss" scoped>
.pbi-header {
	color: $text-color;
	margin-bottom: 1rem * $phi-up;
	.type {
		font-weight: bold;
	}
	.name {
		font-size: 1rem * $phi-sr;
	}
	.id {
		font-size: 1rem * $phi;
	}
}
</style>
