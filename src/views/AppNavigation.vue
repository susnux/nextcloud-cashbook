<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ICashbook } from '../models/Cashbook';

import { mdiImport, mdiTrashCan } from '@mdi/js';
import { t } from '@nextcloud/l10n'
import { spawnDialog as spawnNcDialog } from '@nextcloud/vue/functions/dialog'
import { createVNode, getCurrentInstance, render, toRaw, type Component } from 'vue';
import { useRouter } from 'vue-router';
import { deleteCashbook } from '../api/cashbooks';
import { useCashbookStore } from '../store/cashbookStore';

import NcActionButton from '@nextcloud/vue/components/NcActionButton';
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation';
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption';
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem';
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList';
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew';
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper';
import ImportCashbookDialog from '../components/ImportCashbookDialog.vue';
import NewCashbookDialog from '../components/NewCashbookDialog.vue';

const store = useCashbookStore()
const router = useRouter()

async function importCashbook() {
	const cashbook = await spawnNcDialog(ImportCashbookDialog)
	if (cashbook) {
		store.cashbooks.push(cashbook)
		router.push({ name: 'cashbook', params: { cashbook: String(cashbook.id) } })
	}
}

async function onDeleteCashbook(cashbook: ICashbook) {
	await deleteCashbook(cashbook)
	store.cashbooks = store.cashbooks.filter(({ id }) => id !== cashbook.id)
}

async function createNew() {
	spawnDialog<[ICashbook]>(NewCashbookDialog)
}

// TODO: Replace with function from nc-vue when released
function spawnDialog<P extends Array<unknown> = unknown[]>(
	dialog: Component,
	props?: unknown[],
	onClose: (...rest: P) => void = () => {},
): void {
	const el = document.createElement('div')
	const container: HTMLElement = document.body
	container.appendChild(el)

	const vueComponent = createVNode(dialog, {
		...props,
		onclose: (...rest: unknown[]) => {
			onClose(...rest.map(v => toRaw(v)) as P)
			// destroy the component
			render(null, el)
			el.remove()
		},
	})

	// If there is an instance use it to get access to registered components
	const appContext = getCurrentInstance()?.appContext
	if (appContext) {
		vueComponent.appContext = { ...appContext }
	}

	render(vueComponent, el)
}
</script>

<template>
	<NcAppNavigation>
		<NcAppNavigationNew :text="t('cashbook', 'New cashbook')" @click="createNew" />
		<NcAppNavigationCaption
			heading-id="cashbook-app-navigation-your-cashbooks"
			is-heading
			:name="t('cashbook', 'Your cashbooks')" />
		<NcAppNavigationList aria-labelledby="cashbook-app-navigation-your-cashbooks">
			<NcAppNavigationItem
				v-for="cashbook in store.cashbooks"
				:key="cashbook.id"
				:name="cashbook.name"
				:to="{ name: 'cashbook', params: { cashbook: String(cashbook.id) } }">
				<template #actions>
					<NcActionButton @click="onDeleteCashbook(cashbook)">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCan" :size="20" />
						</template>
						Delete
					</NcActionButton>
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>
		<template #footer>
			<NcAppNavigationList>
				<NcAppNavigationItem :name="t('cashbook', 'Import cashbook')" @click="importCashbook">
					<template #icon>
						<NcIconSvgWrapper :path="mdiImport" :size="20" />
					</template>
				</NcAppNavigationItem>
			</NcAppNavigationList>
		</template>
	</NcAppNavigation>
</template>
