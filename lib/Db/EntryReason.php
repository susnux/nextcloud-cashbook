<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Db;

final class EntryReason {
	public const string DayOpening = 'day-opening';
	public const string DayClosing = 'day-closing';

	public const string Correction = 'correction';

	public const string IncomeBankTransfer = 'income-bank';
	public const string IncomeSales = 'income-sales';
	public const string IncomeMisc = 'income-misc';

	public const string ExpenseBankTransfer = 'expense-bank';
	public const string ExpenseGoods = 'expense-goods';
	public const string ExpenseSalary = 'expense-salary';
	public const string ExpenseTips = 'expense-tips';
	public const string ExpenseChange = 'expense-change';
	public const string ExpenseRefund = 'expense-refund';
	public const string ExpenseMisc = 'expense-misc';
}
