<template>
	<div
		class="object-card"
		:class="{ 'no-grow': !grow }"
		:style="'width: ' + responsive(width)"
	>
		<div class="header">
			<div class="title">
				{{ title?.length ? title : $t(title_placeholder) }}
			</div>
			<div class="subtitle">
				{{ subtitle?.length ? subtitle : $t(subtitle_placeholder) }}
			</div>
		</div>

		<div class="body">
			<slot name="form"></slot>
		</div>

		<div class="plus">
			<slot name="plus"></slot>
		</div>

		<div class="footer">
			<slot name="action"></slot>
		</div>
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { defineComponent } from 'vue';
import { is_mobile } from '@/tools/screenSizes';

export default defineComponent({
	name: 'ObjectCard',
	props: {
		width: { type: String },
		actionButton: { type: String },
		cancelButton: { type: String },
		deleteButton: { type: String },
		title_db_name: { type: String },
		subtitle_db_name: { type: String },
		title_placeholder: { type: String },
		subtitle_placeholder: { type: String },
		record: { type: Object, required: true },
	},
	setup(props) {
		const grow = ref(true);
		const title = computed(() => props.record?.[props.title_db_name] ?? '');
		const subtitle = computed(() => props.record?.[props.subtitle_db_name] ?? '');

		const responsive = (columnWidth) => {
			if (is_mobile()) {
				grow.value = true;
				return;
			}

			if (!is_mobile() && !columnWidth) {
				grow.value = true;
				return 'calc(100% - 2rem)';
			}

			if (!is_mobile() && columnWidth) {
				grow.value = false;
				return columnWidth;
			}
		};

		return { grow, title, subtitle, responsive };
	},
});
</script>

<style scoped lang="scss">
.object-card {
	height: 100%;
	margin: 1rem;
	flex-grow: 1;
	display: flex;
	overflow: hidden;
	border-radius: 4px;
	flex-direction: column;
	max-height: calc(100% - 2rem);
	background-color: rgba(255, 255, 255, 0.7);
	box-shadow: 3px 3px 7px rgba(0, 0, 0, 0.1);

	&.no-grow {
		flex-grow: 0;
	}

	.header {
		padding: 1rem;
		flex-shrink: 0;
		color: $primary-color;
		border-radius: 4px 4px 0 0;
		border-bottom: 1px solid rgba(0, 0, 0, 0.1);
		background-color: rgba(#ffffff, 0.65);

		.title {
			font-weight: bold;
			font-size: 1rem * $phi-up;
		}
		.subtitle {
			font-size: 1rem * $phi;
		}
	}
	.body {
		flex-grow: 1;
		min-height: 0;
		padding: 1rem;
		overflow-y: auto;
		color: #ffffff;
		overflow-x: hidden;
	}

	.footer {
		flex-shrink: 0;
	}
}
</style>
