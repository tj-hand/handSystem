<template>
	<div
		class="row"
		:class="schema.class"
		v-if="schema.type === 'row'"
	>
		<NoRecord v-if="schema?.class === 'no-record' && record?.record === null" />
		<div
			v-else
			class="col"
			:key="index"
			:class="column.class"
			v-for="(column, index) in schema.cols"
			:style="'width: ' + responsive(column.width)"
		>
			<template v-if="Array.isArray(column.content)">
				<BuilderCore
					:key="subIndex"
					:record="record"
					:schema="subSchema"
					:formSize="formSize"
					@updateData="updaterecord"
					v-for="(subSchema, subIndex) in column.content"
				/>
			</template>

			<template v-else-if="column.content?.type === 'row'">
				<BuilderCore
					:key="index"
					:record="record"
					:formSize="formSize"
					:schema="column.content"
					@updateData="updaterecord"
				/>
			</template>

			<BuilderCore
				v-else
				@updateData="updaterecord"
				v-bind="{ schema: column.content || {}, record, formSize }"
			/>
		</div>
	</div>

	<RouterView v-if="schema.type === 'field' && schema.props?.component === 'virtualRouter'" />

	<component
		@updateData="updaterecord"
		:is="schema.props.component"
		v-bind="{ formSize, record, params: schema.props || {} }"
		v-else-if="schema?.type === 'field' && schema?.props?.component"
	/>
</template>

<script>
import { defineComponent } from 'vue';
import { is_mobile } from '@/tools/screenSizes';
import NoRecord from '@/components/NoRecord.vue';
import PBIHeader from '@/components/PBIHeader.vue';
import PageTitle from '@/components/PageTitle.vue';
import TextField from '@/components/TextField.vue';
import AreaField from '@/components/AreaField.vue';
import SelectList from '@/components/SelectList.vue';
import ObjectCard from '@/components/ObjectCard.vue';
import ScopeSetter from '@/components/ScopeSetter.vue';
import ButtonField from '@/components/ButtonField.vue';
import FormSubtitle from '@/components/FormSubtitle.vue';
import PasswordField from '@/components/PasswordField.vue';
import PowerBIViewer from '@/components/PowerBIViewer.vue';
import CheckboxField from '@/components/CheckboxField.vue';
import PBIObjectPages from '@/components/PBIObjectPages.vue';
import CheckableItemList from '@/components/CheckableItemList.vue';
import HorizontalSubmenu from '@/components/HorizontalSubmenu.vue';

export default defineComponent({
	name: 'BuilderCore',
	components: {
		NoRecord,
		PBIHeader,
		PageTitle,
		AreaField,
		TextField,
		SelectList,
		ObjectCard,
		ButtonField,
		ScopeSetter,
		FormSubtitle,
		PasswordField,
		PowerBIViewer,
		CheckboxField,
		PBIObjectPages,
		CheckableItemList,
		HorizontalSubmenu,
	},
	props: {
		formSize: { type: String, default: '' },
		schema: { type: Object, required: true },
		record: { type: Object, required: true },
	},
	emits: ['updateData'],
	setup(props, { emit }) {
		const updaterecord = (recordUpdated) => {
			emit('updateData', recordUpdated);
		};

		const responsive = (columnWidth) => {
			return is_mobile() ? '100%' : columnWidth;
		};

		return { responsive, updaterecord };
	},
});
</script>

<style lang="scss" scoped>
.row {
	gap: 0 1rem;
	width: 100%;
	display: flex;
	flex-wrap: wrap;

	&.scroll {
		flex: 1;
		min-height: 0;
		overflow-y: auto;
	}

	&.grow {
		flex-grow: 1;
	}

	&.no-record {
		flex-grow: 1;
	}

	.col {
		display: flex;
		flex-direction: column;
		box-sizing: border-box;

		&.top-space {
			padding-top: 1rem * $phi-up;
		}

		&.vertical-justify {
			flex: 1 1 auto;
			padding: 0 1rem;
			width: calc(100% - 1rem);
			justify-content: space-between;
		}
	}
}
</style>
