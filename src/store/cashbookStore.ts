/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ICashbook } from "../models/Cashbook";

import { defineStore } from "pinia";
import { ref } from "vue";
import { fetchCashbooks } from "../api/cashbooks";

import logger from "../utils/logger.ts";

export const useCashbookStore = defineStore('cashbook', () => {
	const cashbooks = ref<ICashbook[]>([])
	const loaded = ref(false)

	async function init() {
		try {
			const apiResult = await fetchCashbooks()
			cashbooks.value = apiResult
			loaded.value = true
			logger.debug('Successfully loaded cashbooks', { cashbooks: apiResult })
		} catch (error) {
			logger.error('Failed to fetch cashbooks', { error })
		}
	}

	init()

	return {
		cashbooks,
		loaded,
	}
})