<template>
	<div class="horizontal-submenu">
		<div
			:key="index"
			class="item"
			@click="goTo(item.link_to)"
			v-for="(item, index) in params.items"
		>
			{{ $t(item.label) }}
		</div>
	</div>
</template>
<script>
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';

export default defineComponent({
	name: 'HorizontalSubmenu',
	props: {
		params: { type: Object },
	},
	setup(props) {
		const router = useRouter();
		const goTo = (page) => {
			router.push({ name: page });
		};
		onMounted(() => {
			if (props.params?.items?.length) goTo(props.params.items[0].link_to);
		});
		return { goTo };
	},
});
</script>

<style scoped lang="scss">
.horizontal-submenu::-webkit-scrollbar {
	height: 5px;
}

.horizontal-submenu {
	width: 100%;
	display: flex;
	overflow-x: auto;
	overflow-y: hidden;
	flex-direction: row;
	color: $primary-color;
	box-sizing: border-box;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	background-color: rgba(255, 255, 255, 0.38);
	.item {
		cursor: pointer;
		margin-right: 1rem;
		transition: all 0.5s ease;
		padding: (1rem * $phi) 1rem;
		&:hover {
			background-color: rgba(0, 0, 0, 0.05);
		}
	}
}
</style>
