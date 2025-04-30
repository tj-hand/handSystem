<template>
	<div class="object-title">
		<div class="title">
			{{ title?.length ? title : $t(params.title_placeholder) }}
		</div>
		<div class="subtitle">
			{{ subtitle?.length ? subtitle : $t(params.subtitle_placeholder) }}
		</div>
	</div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'ObjectTitle',
	props: {
		params: { type: Object },
		formData: { type: Object, required: true },
	},
	setup(props) {
		const title = props.formData[props.params.title_db_name];
		const subtitle = props.formData[props.params.subtitle_db_name];

		return { title, subtitle };
	},
});
</script>

<style scoped lang="scss">
.object-title {
	width: 100%;
	color: $text-color;
	margin-bottom: 1rem;
	box-sizing: border-box;
	padding: 1rem 0 (1rem * $phi);
	border-bottom: 1px solid $text-color;

	.title {
		font-weight: bold;
		font-size: 1rem * $phi-up;
	}
	.subtitle {
		font-size: 1rem * $phi;
	}
}
</style>
