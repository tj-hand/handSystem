<template>
	<div class="welcome">
		<PageTitle title="auth.welcome.title" />
		<div class="data-container">
			<div class="bookmarks">
				<FormSubtitle :params="{ label: 'auth.welcome.bookmarks' }" />
				<div
					:key="index"
					class="item"
					@click="goToObject(item.id)"
					v-for="(item, index) in bookmarksList"
				>
					{{ item.local_name }}
				</div>
			</div>
			<div class="lastviews">
				<FormSubtitle :params="{ label: 'auth.welcome.lastviews' }" />
				<div
					:key="index"
					class="item"
					@click="goToObject(item.id)"
					v-for="(item, index) in lastViewsList"
				>
					{{ item.local_name }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import { onMounted } from 'vue';
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import apiService from '@/api/apiService';
import PageTitle from '@/components/PageTitle.vue';
import FormSubtitle from '@/components/FormSubtitle.vue';

export default defineComponent({
	name: 'Welcome',
	components: {
		PageTitle,
		FormSubtitle,
	},
	setup() {
		const router = useRouter();
		const bookmarksList = ref([]);
		const lastViewsList = ref([]);

		const getBookmarks = async () => {
			const { success, bookmarks } = await apiService.welcome.bookmarks();
			if (success) bookmarksList.value = bookmarks;
		};

		const getLastViews = async () => {
			const { success, lastViews } = await apiService.welcome.lastviews();
			if (success) lastViewsList.value = lastViews;
		};

		const goToObject = (id) => {
			router.push({ name: 'Bis', params: { id: id } });
		};

		onMounted(() => {
			getBookmarks();
			getLastViews();
		});

		return { bookmarksList, lastViewsList, goToObject };
	},
});
</script>

<style scoped lang="scss">
.welcome {
	width: 100%;
	box-sizing: border-box;

	.data-container {
		flex-grow: 1;
		padding: 1rem;
		display: flex;
		flex-direction: row;
		gap: 1rem * $phi-double;

		.bookmarks,
		.lastviews {
			width: 38%;

			.item {
				padding: 1rem;
				cursor: pointer;
				border-radius: 4px;
				color: $primary-color;
				transition: all 0.5s ease;
				margin: (1rem * $phi-down) 0;
				border: 1px solid rgba(0, 0, 0, 0.1);
				background-color: rgba(255, 255, 255, 0.38);

				&:hover {
					background-color: rgba(255, 255, 255, 0.62);
				}
			}
		}
	}
}

@media (max-width: 1024px) {
	.welcome {
		.data-container {
			flex-direction: column;
			.bookmarks,
			.lastviews {
				width: 100%;
			}
		}
	}
}
</style>
