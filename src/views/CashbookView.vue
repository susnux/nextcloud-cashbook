<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ICashbookEntry } from '../models/CashbookEntry';

import { t } from '@nextcloud/l10n'
import { computed, ref, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
import { fetchEntriesByDate } from '../api/cashbooks';
import { useCashbookStore } from '../store/cashbookStore';

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import CashbookTable from '../components/CashbookTable.vue';

const props = defineProps<{
	cashbookId: number
	entryId?: number
}>()

const router = useRouter()
const store = useCashbookStore()

const entries = ref<ICashbookEntry[]>([])
const cashbook = computed(() => store.cashbooks.find((cb) => cb.id === props.cashbookId))
watchEffect(async () => {
	if (cashbook.value) {
		//entries.value = await fetchEntries(cashbook.value, { offset: 0, limit: 10 })
		entries.value = await fetchEntriesByDate(cashbook.value, { begin: new Date('2025-01-01T00:00:00+01:00'), end: new Date('2025-03-31T23:59:00+02:00') })
	}
})

watchEffect(() => {
	if (store.loaded && store.cashbooks.find((cb) => cb.id === props.cashbookId) === undefined) {
		router.replace({ name: 'not-found' })
	}
})
</script>

<template>
	<NcEmptyContent
		v-if="!cashbook"
		:class="$style.emptyContent"
		:name="t('cashbook', 'Loading cashbook')">
		<template #icon>
			<NcLoadingIcon />
		</template>
	</NcEmptyContent>

	<div v-else>
		<h2 :class="$style.heading">{{ cashbook.name }}</h2>

		<CashbookTable :cashbook :entries />
	</div>
</template>

<style module lang="scss">
.emptyContent {
	height: 100%;
	margin-block: calc(2 * var(--app-navigation-padding) + var(--default-clickable-area));
}

.heading {
	padding-inline: calc(2 * var(--app-navigation-padding) + var(--default-clickable-area));
	margin-block: var(--app-navigation-padding);
	line-height: 1.5;
	font-size: calc(var(--default-clickable-area) / 1.5);
}

@media print {
	@page{
		size: a4;
		margin-inline: 19mm 6mm;
		margin-block: 10mm;
	}

	:global(header), :global(.app-navigation) {
		display: none !important;
	}

	:global(#content.app-cashbook) {
		margin: 0 !important;
		min-height: 100%;
		height: fit-content;
		width: 100%;
	}

	:global(#content-vue.app-cashbook) {
		border: none;
		border-radius: 0;
		width: 100%;
		height: fit-content;
	}

	:global(.app-cashbook #app-content-vue) {
		min-height: 100vh;
	}
}
</style>
