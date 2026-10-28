/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from "@nextcloud/router"
import { createRouter, createWebHistory, type RouteLocation, type RouteLocationNamedRaw, type RouteLocationNormalized, type RouteParams } from "vue-router"
import EmptyView from "./views/EmptyView.vue"

const CashbookView = () => import('./views/CashbookView.vue')
const CashbookExportView = () => import('./views/ExportView.vue')

const baseUrl = generateUrl('/apps/cashbook')

function normalizeParam(paramName: string, routeParams: RouteParams) {
	return typeof routeParams[paramName] === 'string' ? routeParams[paramName] : routeParams[paramName][0]
}

export const routes = [
	{ path: '/', name: 'root', component: EmptyView },
	{ path: '/not-found', name: 'not-found', component: EmptyView },
	{
		path: '/:cashbook(\\d+)',
		name: 'cashbook',
		component: CashbookView,
		props: (route: RouteLocationNormalized) => ({ cashbookId: Number.parseInt(normalizeParam('cashbook', route.params)) }),
	},
	{
		path: '/:cashbook(\\d+)/:entry(\\d+)',
		name: 'cashbook-entry',
		component: CashbookView,
		props: (route: RouteLocationNormalized) => ({
			cashbookId: Number.parseInt(normalizeParam('cashbook', route.params)),
			entryId: Number.parseInt(normalizeParam('entry', route.params))
		}),
	},
	{
		path: '/:cashbook(\\d+)/export',
		name: 'cashbook-export',
		component: CashbookExportView,
		props: (route: RouteLocationNormalized) => ({
			cashbookId: Number.parseInt(normalizeParam('cashbook', route.params)),
		}),
	},
]

export const router = createRouter({
	history: createWebHistory(baseUrl),

	routes,
})