<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ICashbook } from '../models/Cashbook';
import { EntryReasonReadable, type ICashbookEntry } from '../models/CashbookEntry';

import { mdiEye, mdiInformation, mdiPencil, mdiTrashCan } from '@mdi/js';
import { getCanonicalLocale } from '@nextcloud/l10n';
import { useFormatDateTime } from '@nextcloud/vue';
import { computed } from 'vue';

import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { formatCurrency } from '../utils/formatter.ts';

const props = defineProps<{
	cashbook: ICashbook
	modelValue: ICashbookEntry
}>()

const { formattedTime } = useFormatDateTime(props.modelValue.date, { relativeTime: false, format: { dateStyle: 'medium', timeStyle: 'short' } })
const formattedTax = computed(() => props.modelValue.tax?.toLocaleString(getCanonicalLocale(), { style: 'percent' }) ?? '')

const formattedReason = computed(() => props.modelValue.reason && EntryReasonReadable[props.modelValue.reason])
</script>

<template>
	<tr :class="modelValue.amendment ? $style.amended : ''">
		<td>{{ modelValue.id }}</td>
		<td>
			<div :class="$style.wrappedText" v-text="formattedTime" />
		</td>
		<td>
			<div :class="$style.wrappedText" v-text="formattedReason" />
		</td>
		<td>
			<div :class="$style.wrappedText" v-text="modelValue.text" />
		</td>
		<td>{{ formattedTax }}</td>
		<td>{{ 'debit' in modelValue ? formatCurrency(modelValue.debit, cashbook.currency) : '' }}</td>
		<td>{{ 'credit' in modelValue ? formatCurrency(modelValue.credit, cashbook.currency) : '' }}</td>
		<td :class="$style.balanceColumn">
			{{ formatCurrency(modelValue.balance, cashbook.currency) }}
		</td>
		<td>
			<NcActions force-menu>
				<template #icon>
					<NcIconSvgWrapper v-if="modelValue.amendment || modelValue.original" :path="mdiInformation" />
					<NcIconSvgWrapper v-else :path="mdiPencil" />
				</template>

				<NcActionLink
					v-if="modelValue.original"
					name="Show amended entry"
					:to="{ name: 'cashbook-entry', params: { cashbook: String(cashbook.id), entry: String(modelValue.original) } }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiEye" />
					</template>
				</NcActionLink>

				<NcActionLink
					v-else-if="modelValue.amendment"
					name="Show amendment"
					:to="{ name: 'cashbook-entry', params: { cashbook: String(cashbook.id), entry: String(modelValue.amendment) } }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiEye" />
					</template>
				</NcActionLink>

				<template v-else>
					<NcActionButton name="Edit">
						<template #icon>
							<NcIconSvgWrapper :path="mdiPencil" />
						</template>
					</NcActionButton>
					<NcActionButton name="Delete">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCan" />
						</template>
					</NcActionButton>
				</template>
			</NcActions>
		</td>
	</tr>
</template>

<style module>
.wrappedText {
	text-wrap: wrap;
}
.amended td {
	text-decoration: line-through;
}
.balanceColumn {
	font-weight: bold;
}
</style>