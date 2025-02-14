<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-implements QBMapper<Cashbook>
 */
class CashbookMapper extends QBMapper {

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, 'cashbook_cashbooks', Cashbook::class);
	}

	public function getCashbook(int $id): Cashbook {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),
			);
		return $this->findEntity($qb);
	}
}
