<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000001Date20250214140000 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changedSchema = null;

		if (!$schema->hasTable('cashbook_cashbooks')) {
			$this->createCashbooksTable($schema);
			$changedSchema = $schema;
		}

		if ($schema->hasTable('cashbook_cashbook_entries')) {
			$schema->dropTable('cashbook_cashbook_entries');
		}
		if (!$schema->hasTable('cashbook_cashbook_entries')) {
			$this->createCashbookEntriesTable($schema);
			$changedSchema = $schema;
		}

		return $changedSchema;
	}

	private function createCashbooksTable(ISchemaWrapper &$schema): void {
		$table = $schema->createTable('cashbook_cashbooks');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$table->addColumn('name', Types::STRING, [
			'notnull' => false,
		]);
		$table->addColumn('account', Types::STRING, [
			'notnull' => false,
		]);
		$table->addColumn('owner', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('currency', Types::STRING, [
			'notnull' => true,
			'length' => 3,
		]);

		$table->setPrimaryKey(['id']);
	}

	private function createCashbookEntriesTable(ISchemaWrapper &$schema): void {
		$table = $schema->createTable('cashbook_cashbook_entries');

		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('cashbook_id', Types::BIGINT, [
			'notnull' => true,
		]);
		$table->addColumn('datetime', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('amount_raw', Types::STRING, [
			'notnull' => false,
			'length' => 127,
		]);
		$table->addColumn('balance_raw', Types::STRING, [
			'notnull' => false,
			'length' => 127,
		]);
		$table->addColumn('tax_raw', Types::STRING, [
			'notnull' => false,
			'length' => 32,
		]);
		$table->addColumn('reason', Types::STRING, [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('reference', Types::STRING, [
			'notnull' => false,
			'length' => 127,
		]);
		$table->addColumn('reference_date', Types::DATE_IMMUTABLE, [
			'notnull' => false,
		]);
		$table->addColumn('text', Types::TEXT, [
			'notnull' => false,
			'length' => 127,
		]);

		$table->setPrimaryKey(['id'], 'cashbook_entry_index');
		$table->addForeignKeyConstraint(
			'cashbook_cashbooks',
			['cashbook_id'],
			['id'],
		);
	}
}
