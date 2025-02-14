<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Controller;

use Exception;
use InvalidArgumentException;
use OCA\Cashbook\AppInfo\Application;
use OCA\Cashbook\Db\Cashbook;
use OCA\Cashbook\Db\CashbookMapper;
use OCA\Cashbook\Db\Entry;
use OCA\Cashbook\Db\EntryMapper;
use OCA\Cashbook\Db\EntryReason;
use OCA\Cashbook\ImportService\IImportService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IDBConnection;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {

	public function __construct(
		IRequest $request,
		protected IDBConnection $db,
		protected \OCP\IL10N $l10n,
		protected LoggerInterface $logger,
		protected CashbookMapper $cashbookMapper,
		protected EntryMapper $entryMapper,
		protected ?string $userId,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Cashbook>, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/cashbooks')]
	public function index(): DataResponse {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')->from('cashbook_cashbooks')->executeQuery();
		$cashbooks = array_map(fn ($row) => Cashbook::fromRow($row), $result->fetchAll());
		return new DataResponse(
			$cashbooks
		);
	}

	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/cashbooks/{id}')]
	public function getCashbook(int $id): DataResponse {
		try {
			$cashbook = $this->cashbookMapper->getCashbook($id);
		} catch (DoesNotExistException) {
			throw new OCSNotFoundException();
		}
		if ($this->userId !== $cashbook->getOwner()) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($cashbook);
	}

	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/cashbooks/{id}/entries')]
	public function getCashbookEntries(int $id, int $offset = 0, int $limit = 20): DataResponse {
		if ($limit > 50) {
			throw new OCSBadRequestException('Maximum limit exceeded');
		}

		try {
			$cashbook = $this->cashbookMapper->getCashbook($id);
		} catch (DoesNotExistException) {
			throw new OCSNotFoundException();
		}
		if ($this->userId !== $cashbook->getOwner()) {
			throw new OCSNotFoundException();
		}

		$entries = $this->entryMapper->getEntries($cashbook, $offset, $limit);
		return new DataResponse($entries);
	}

	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/cashbooks/{id}/entries/print')]
	public function getCashbookEntriesPrint(int $id, string $begin, string $end): DataResponse {
		try {
			$utcTimezone = new \DateTimeZone('Utc');
			$dateBegin = (new \DateTimeImmutable($begin))->setTimezone($utcTimezone);
			$dateEnd = (new \DateTimeImmutable($end))->setTimezone($utcTimezone);
		} catch (Exception) {
			throw new OCSBadRequestException('Invalid date param passed');
		}

		try {
			$cashbook = $this->cashbookMapper->getCashbook($id);
		} catch (DoesNotExistException) {
			throw new OCSNotFoundException();
		}
		if ($this->userId !== $cashbook->getOwner()) {
			throw new OCSNotFoundException();
		}

		$entries = $this->entryMapper->getEntriesByDate($cashbook, $dateBegin, $dateEnd);
		/** @var Entry[] */
		$collapsedEntries = [];
		foreach ($entries as $entry) {
			$last = end($collapsedEntries);
			if ($last === false
				|| $entry->getReason() !== EntryReason::IncomeSales
				|| $last->getReason() !== $entry->getReason()
				|| $last->getTaxRaw() !== $entry->getTaxRaw()
				|| $last->getText() !== ''
				|| $entry->getText() !== '') {
				$collapsedEntries[] = $entry;
				continue;
			}

			$last->setAmount(
				$last->getAmount()->plus($entry->getAmount())
			);
			$last->setBalance(
				$last->getBalance()->plus($entry->getAmount())
			);
			$last->setDatetime($entry->getDatetime());
		}

		return new DataResponse($collapsedEntries);
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Cashbook>, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/cashbooks/{id}')]
	public function deleteCashbook(int $id): DataResponse {
		try {
			$cashbook = $this->cashbookMapper->getCashbook($id);
		} catch (DoesNotExistException) {
			throw new OCSForbiddenException();
		}

		// Only owner is allowed to delete the cashbook
		if ($cashbook->getOwner() !== $this->userId) {
			throw new OCSForbiddenException();
		}

		$this->cashbookMapper->delete($cashbook);
		return new DataResponse([]);
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Cashbook>, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/cashbooks')]
	public function createCashbook(
		string $name,
		string $currency,
		?string $importFormat = null,
	): DataResponse {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('cashbook_cashbooks')
			->setValue('name', $qb->createNamedParameter($name))
			->setValue('currency', $qb->createNamedParameter($currency))
			->executeStatement();
		return new DataResponse();
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Cashbook>, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/cashbooks/import')]
	public function importCashbook(
		string $import,
	): DataResponse {
		$files = $this->request->getUploadedFile('files');
		if ($files === null) {
			throw new OCSBadRequestException('No files provided');
		}

		// Now check if the format is valid and exists as an importer
		if (!class_exists($import)) {
			throw new OCSBadRequestException('Unsupported import format');
		}
		$implementedInterfaces = class_implements($import);
		if ($implementedInterfaces === false || !in_array(IImportService::class, $implementedInterfaces)) {
			throw new OCSBadRequestException('Unsupported import format');
		}

		/** @var IImportService $importer */
		$importer = \OCP\Server::get($import);
		try {
			$cashbook = $importer->import($files);
			$this->logger->debug('Successfully imported cashbook {name} ({id})', ['name' => $cashbook->getName(), 'id' => $cashbook->getId()]);
			return new DataResponse(['id' => $cashbook->getId()]);
		} catch (InvalidArgumentException $error) {
			$this->logger->debug('Failed to import cashbook', ['exception' => $error]);
			throw new OCSBadRequestException('Invalid file format');
		}
	}

}
