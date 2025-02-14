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
 * @template-implements QBMapper<Entry>
 */
class EntryMapper extends QBMapper {

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, 'cashbook_cashbook_entries', Entry::class);
	}

	public function getEntries(Cashbook $cashbook, int $offset = 0, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('cashbook_id', $qb->createNamedParameter($cashbook->getId(), IQueryBuilder::PARAM_INT)),
			)
			->setMaxResults($limit)
			->setFirstResult($offset);
		$entries = $this->findEntities($qb);
		array_walk($entries, fn (Entry $entry) => $entry->setCashbook($cashbook));
		return $entries;
	}

	public function getEntriesByDate(Cashbook $cashbook, \DateTimeImmutable $begin, \DateTimeImmutable $end): array {
		$qbStart = $this->db->getQueryBuilder();
		$qbStart->select('id')
			->from($this->tableName)
			->where(
				$qbStart->expr()->eq('cashbook_id', $qbStart->createNamedParameter($cashbook->getId(), IQueryBuilder::PARAM_INT)),
				$qbStart->expr()->eq('reason', $qbStart->createNamedParameter(EntryReason::DayOpening, IQueryBuilder::PARAM_STR)),
				$qbStart->expr()->gte('datetime', $qbStart->createNamedParameter($begin, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)),
			)
			->setMaxResults(1);
		$id = $qbStart->executeQuery()->fetchOne();

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('cashbook_id', $qb->createNamedParameter($cashbook->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->gte('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),
				$qb->expr()->gte('datetime', $qb->createNamedParameter($begin, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)),
				$qb->expr()->lte('datetime', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)),
			);
		$entries = $this->findEntities($qb);
		array_walk($entries, fn (Entry $entry) => $entry->setCashbook($cashbook));
		return $entries;
	}
}
