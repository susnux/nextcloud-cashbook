/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from "@nextcloud/typings/ocs";
import type { ICashbook } from "../models/Cashbook";

import { generateOcsUrl } from "@nextcloud/router";
import axios from '@nextcloud/axios'
import type { ICashbookEntry } from "../models/CashbookEntry";

enum API_ENDPOINT {
	Cashbook = 'cashbooks'
}

interface ApiOptions {
	version: '1'
}

function generateApiRoute(
	endpoint: API_ENDPOINT,
	path = '',
	options: ApiOptions = { version: '1' },
): string {
	const API_BASE = 'apps/cashbook/api'
	return generateOcsUrl(`${API_BASE}/v${options.version}/{endpoint}`, { endpoint }) + path
}

/**
 * Fetch list of available cashbooks for the current user
 */
export async function fetchCashbooks(): Promise<ICashbook[]> {
	const { data } = await axios.get<OCSResponse<ICashbook[]>>(generateApiRoute(API_ENDPOINT.Cashbook))
	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return data.ocs.data
}

/**
 * Fetch a single cashbook by its id
 *
 * @param id - The cashbook id
 */
export async function fetchCashbook(id: number): Promise<ICashbook> {
	const { data } = await axios.get<OCSResponse<ICashbook>>(generateApiRoute(API_ENDPOINT.Cashbook, `/${id}`))
	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return data.ocs.data
}

type RawCashbookEntry = Omit<ICashbookEntry, 'date' | 'referenceDate'> & {
	date: string
	referenceDate?: string
}

/**
 * Convert the ISO date strings of an entry returned by the API into Date objects
 *
 * @param entry - The raw entry as returned by the API
 */
function parseEntry(entry: RawCashbookEntry): ICashbookEntry {
	return {
		...entry,
		date: new Date(entry.date),
		referenceDate: entry.referenceDate ? new Date(entry.referenceDate) : undefined,
	} as ICashbookEntry
}

type FetchEntryOption = {
	offset: number
	limit: number
}

/**
 * Fetch list of available cashbooks for the current user
 */
export async function fetchEntries(cashbook: ICashbook, { offset, limit }: FetchEntryOption): Promise<ICashbookEntry[]> {
	const { data } = await axios.get<OCSResponse<RawCashbookEntry[]>>(
		generateApiRoute(API_ENDPOINT.Cashbook, `/${cashbook.id}/entries?limit=${limit}&offset=${offset}`),
	)
	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return data.ocs.data.map(parseEntry)
}

type FetchEntryByDateOption = {
	begin: Date
	end: Date
}

/**
 * Fetch list of available cashbooks for the current user
 */
export async function fetchEntriesByDate(cashbook: ICashbook, { begin, end }: FetchEntryByDateOption): Promise<ICashbookEntry[]> {
	const { data } = await axios.get<OCSResponse<RawCashbookEntry[]>>(
		generateApiRoute(API_ENDPOINT.Cashbook, `/${cashbook.id}/entries/print?begin=${begin.toISOString()}&end=${end.toISOString()}`),
	)
	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return data.ocs.data.map(parseEntry)
}



export async function createCashbook(cashbook: Omit<ICashbook, 'id'>): Promise<ICashbook> {
	const postData = { ...cashbook }
	delete (postData as Partial<ICashbook>).id

	const { data } = await axios.post<OCSResponse<ICashbook>>(
		generateApiRoute(API_ENDPOINT.Cashbook),
		postData,
	)

	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return data.ocs.data
}

/**
 * Import a cashbook from a SumUp POS Pro (German) export.
 *
 * @param files - The cashbook CSV (`Kassenbuch_*.csv`) and optionally the daily closing report CSV
 * @return The newly created cashbook
 */
export async function importSumupCashbook(files: File[]): Promise<ICashbook> {
	const formData = new FormData()
	formData.append('import', 'OCA\\Cashbook\\ImportService\\SumupPosProGermany')
	for (const file of files) {
		formData.append('files[]', file)
	}

	const { data } = await axios.post<OCSResponse<{ id: number }>>(
		generateApiRoute(API_ENDPOINT.Cashbook, '/import'),
		formData,
	)

	if (data.ocs.meta.status !== 'ok') {
		throw new Error(data.ocs.meta.message, { cause: data.ocs })
	}

	return await fetchCashbook(data.ocs.data.id)
}

export async function deleteCashbook(cashbook: ICashbook): Promise<void> {
	await axios.delete(generateApiRoute(API_ENDPOINT.Cashbook, `/${cashbook.id}`))
}