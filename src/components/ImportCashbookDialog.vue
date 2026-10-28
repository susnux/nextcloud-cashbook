<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ICashbook } from '../models/Cashbook';

import { t } from '@nextcloud/l10n';
import { computed, ref } from 'vue';
import { importSumupCashbook } from '../api/cashbooks';
import logger from '../utils/logger.ts';

import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcFilePicker from '@nextcloud/vue/components/NcFilePicker'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const emit = defineEmits<{
	(e: 'close', cashbook?: ICashbook): void
}>()

const files = ref<File[]>([])
const error = ref('')
const imported = ref<ICashbook>()

const buttons = computed<InstanceType<typeof NcDialog>['buttons']>(() => [
	{
		variant: 'tertiary',
		label: t('cashbook', 'Cancel'),
	},
	{
		variant: 'primary',
		label: t('cashbook', 'Import cashbook'),
		disabled: files.value.length === 0,
		callback: onImport,
	},
])

/**
 * Upload the selected files, keep the dialog open on failure.
 */
async function onImport() {
	error.value = ''
	try {
		imported.value = await importSumupCashbook(files.value)
	} catch (e) {
		logger.error('Failed to import cashbook', { error: e })
		error.value = t('cashbook', 'Could not import the cashbook. Please make sure you selected the cashbook export (Kassenbuch_….csv) and optionally the matching daily closing report.')
		return false
	}
}

/**
 * @param open - The new open state of the dialog
 */
function onUpdateOpen(open: boolean) {
	if (!open) {
		emit('close', imported.value)
	}
}
</script>

<template>
	<NcDialog
		:buttons="buttons"
		:name="t('cashbook', 'Import SumUp cashbook')"
		@update:open="onUpdateOpen">
		<p>
			{{ t('cashbook', 'Select the cashbook CSV export of the SumUp POS Pro. Optionally also select the daily closing report to import the day openings and closings.') }}
		</p>
		<NcFilePicker
			:accept="['.csv', 'text/csv']"
			:label="t('cashbook', 'Select files')"
			multiple
			@pick="files = $event" />
		<ul v-if="files.length > 0" :class="$style.fileList">
			<li v-for="file in files" :key="file.name">
				{{ file.name }}
			</li>
		</ul>
		<NcNoteCard v-if="error" type="error" :text="error" />
	</NcDialog>
</template>

<style lang="scss" module>
.fileList {
	list-style: disc;
	padding-inline-start: calc(3 * var(--default-grid-baseline));
	margin-block: var(--default-grid-baseline);
}
</style>
