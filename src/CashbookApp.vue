<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCurrentUser } from '@nextcloud/auth';
import { t } from '@nextcloud/l10n';

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import AppNavigation from './views/AppNavigation.vue';

const showNavigation = getCurrentUser() !== null
</script>

<template>
	<NcContent app-name="cashbook">
		<AppNavigation v-if="showNavigation" />
		<NcAppContent :page-heading="t('cashbook', 'Cashbook')">
			<RouterView />
		</NcAppContent>
	</NcContent>
</template>

<style scoped lang="scss">
#cashbook {
	display: flex;
	justify-content: center;
	margin: 16px;
}
</style>
