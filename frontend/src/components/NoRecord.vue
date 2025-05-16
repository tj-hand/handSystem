<template>
	<div class="no-record">
		<div class="icon">touch_app</div>
		<div class="title">{{ $t('generic.no_record') }}</div>
		<div class="subtitle">{{ $t('generic.add_or_select') }}</div>
	</div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'NoRecord',
	setup() {},
});
</script>

<style scoped lang="scss">
.no-record {
	flex-grow: 1;
	display: flex;
	color: $text-color;
	align-items: center;
	flex-direction: column;
	justify-content: center;

	.icon {
		font-size: 1rem * $phi-double;
	}

	.title {
		padding: 1rem 0;
		font-weight: 100;
		font-size: 1rem * $phi-double;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	}

	.subtitle {
		padding: 1rem;
	}
}
</style>
