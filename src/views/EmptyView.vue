<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import svgCashbook from '../../img/app-dark.svg?raw'

const route = useRoute()

const name = computed(() => (
	route.name === 'not-found'
		? t('cashbook', 'Cashbook not found')
		: t('cashbook', 'No cashbook selected')
	)
)

const generalText = t('cashbook', 'Select a cashbook from the menu or create a new one.')
const notExistText = t('cashbook', 'The cashbook your were looking for does not exist anymore or was unshared from you.')
const description = computed(() => (
	route.name === 'not-found'
		? `${notExistText}\n${generalText}`
		: generalText
))
</script>

<template>
	<NcEmptyContent
		:class="$style.emptyView"
		:name="name"
		:description="description">
		<template #icon>
			<NcIconSvgWrapper :svg="svgCashbook" />
		</template>
	</NcEmptyContent>
</template>

<style module>
.emptyView {
	height: 100%;
	margin-block: calc(2 * var(--app-navigation-padding) + var(--default-clickable-area));
}
</style>
