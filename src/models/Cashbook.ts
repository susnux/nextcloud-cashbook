/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface ICashbook {
	id: number
	name: string
	account: string
	currency: string
}

export class Cashbook implements ICashbook {

	public id: number
	public name: string
	public account: string
	public currency: string

	public constructor(data: ICashbook) {
		this.id = data.id
		this.account = data.account
		this.name = data.name
		this.currency = data.currency
	}

}