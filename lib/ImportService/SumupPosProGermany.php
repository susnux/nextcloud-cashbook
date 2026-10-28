<?php

declare(strict_types=1);

namespace OCA\Cashbook\ImportService;

use Brick\Math\BigRational;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Exception;
use InvalidArgumentException;
use Iterator;
use League\Csv\Reader;
use OCA\Cashbook\Db\Cashbook;
use OCA\Cashbook\Db\CashbookMapper;
use OCA\Cashbook\Db\Entry;
use OCA\Cashbook\Db\EntryMapper;
use OCA\Cashbook\Db\EntryReason;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IDateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * Allow to import the cashbook of the SumUp POS Pro (German version)
 */
class SumupPosProGermany implements IImportService {

	protected ?Iterator $reportIterator = null;
	protected Iterator $cashbookIterator;
	protected Cashbook $cashbook;
	protected \DateTimeZone $timezone;

	protected const string CASHBOOK_FILE_PREFIX = 'Kassenbuch_';
	protected const string CSV_ENCLOSURE = '"';
	protected const string CSV_DELIMITER = ';';

	protected const array CSV_HEADER_MAPPING = [
		'Geschäft' => 'store',
		'Geschäfts ID' => 'storeId',
		'Datum und Uhrzeit' => 'datetime',
		'Belegnummer' => 'reference',
		'Grund' => 'reason',
		'Buchungstext' => 'text',
		'Währung' => 'currency',
		'Einnahmen' => 'debit',
		'Ausgaben' => 'credit',
		'Mehrwertsteuersatz' => 'tax',
		'Betrag MwSt' => 'taxSum',
		'Kassenbestand' => 'total',
	];

	protected const array CSV_REPORT_HEADER_MAPPING = [
		'Eröffnungsdatum' => 'opening',
		'Datum geschlossen' => 'closing',
		'Znumber' => 'znumber',
		'Anfangskasse' => 'balanceStart',
		'Kassen Endbestand' => 'balanceEnd',
	];

	public function __construct(
		IDateTimeZone $timeZoneFactory,
		protected ?string $userId,
		protected LoggerInterface $logger,
		protected CashbookMapper $cashbookMapper,
		protected EntryMapper $entryMapper,
	) {
		$this->timezone = $timeZoneFactory->getTimeZone();
	}

	public function import(array $files, ?array $options = null): Cashbook {
		// As the very first set we need to set the timezone as this is used by all other
		// import related code paths - sadly the dates and times are not exported with timezone information.
		if ($options !== null && isset($options['timezone'])) {
			try {
				$this->timezone = new \DateTimeZone($options['timezone']);
			} catch (Exception) {
				throw new OCSBadRequestException('Invalid timezone option passed.');
			}
		}

		$cashbookCsvFile = '';
		$reportCsvFile = '';

		$this->logger->error('FILES: ' . print_r($files, true));

		foreach ($files['name'] as $index => $name) {
			if (str_starts_with($name, self::CASHBOOK_FILE_PREFIX)) {
				$cashbookCsvFile = $files['tmp_name'][$index];
			} else {
				$reportCsvFile = $files['tmp_name'][$index];
			}
		}

		if ($cashbookCsvFile === '') {
			throw new InvalidArgumentException('Missing CSV file or wrong name.');
		}

		try {
			$cashbookCsv = Reader::createFromPath($cashbookCsvFile);
			$cashbookCsv->setHeaderOffset(0);
			$cashbookCsv->setDelimiter(self::CSV_DELIMITER);
			$cashbookCsv->setEnclosure(self::CSV_ENCLOSURE);
			$this->cashbookIterator = $cashbookCsv->getRecords(
				$this->mapCashbookCsvHeader($cashbookCsv->getHeader())
			);
			$this->cashbookIterator->rewind();
		} catch (\Exception $error) {
			throw new InvalidArgumentException('Cashbook header not readable', previous: $error);
		}

		if ($this->cashbookIterator->valid() === false) {
			throw new InvalidArgumentException('Could not parse cashbook file');
		}

		try {
			$this->logger->error('REPORT: ' . $reportCsvFile);
			// Lets see if we have a report available
			if ($reportCsvFile !== '') {
				$reportCsv = Reader::createFromPath($reportCsvFile);
				$reportCsv->setHeaderOffset(0);
				$reportCsv->setDelimiter(self::CSV_DELIMITER);
				$reportCsv->setEnclosure(self::CSV_ENCLOSURE);
				$this->reportIterator = $reportCsv->getRecords(
					$this->mapReportCsvHeader($reportCsv->getHeader()),
				);
				$this->reportIterator->rewind();
			} else {
				$this->reportIterator = null;
			}
		} catch (\Exception $error) {
			throw new InvalidArgumentException('Report header not readable', previous: $error);
		}

		// Seek the begin of the cashbook according to the report information
		if ($this->reportIterator !== null) {
			$report = $this->reportIterator->current();
			$reportDate = $this->parseDateTime($report['opening']);

			while ($this->cashbookIterator->valid()) {
				$entry = $this->cashbookIterator->current();
				$cashbookDate = $this->parseDateTime($entry['datetime']);
				if ($cashbookDate >= $reportDate) {
					break;
				}
				$this->cashbookIterator->next();
			}
		}

		$entry = $this->cashbookIterator->current();
		$currency = $entry['currency'];
		// Validate the currency
		try {
			Money::of('0', $currency);
		} catch (UnknownCurrencyException) {
			throw new InvalidArgumentException('Cashbook contains unknown currency.');
		}

		$this->cashbook = new Cashbook();
		$this->cashbook->setOwner($this->userId);
		$this->cashbook->setCurrency($currency);
		$this->cashbook->setName($entry['store']);
		$this->cashbook->setAccount($entry['storeId']);

		// Get the initial amount of the cashbooks entry - needed to calculate initial balance
		$amount = $this->parseMoney($entry['debit'] ?: '0', $currency)
			->plus($this->parseMoney($entry['credit'] ?: '0', $currency));
		$total = $this->parseMoney($entry['total'])->minus($amount);

		// If we have a report we need to validate we start with the same money
		if (isset($report) && !$total->isEqualTo($this->parseMoney($report['balanceStart'], $currency))) {
			$this->logger->error('FFF %s %s %s', [(string)$total, (string)$amount, (string)$this->parseMoney('-100,00')]);
			throw new InvalidArgumentException('Report and cashbook do not match on cash balance');
		}

		// everything ok so insert the cashbook and start importing the entries
		$this->cashbookMapper->insert($this->cashbook);

		try {
			if ($this->reportIterator !== null) {
				$this->iterateReport();
			} else {
				$this->iterateCashbook();
			}
		} catch (Exception $e) {
			$this->logger->error('Failed to import cashbook', ['exception' => $e]);
			$this->cashbookMapper->delete($this->cashbook);
			throw new InvalidArgumentException('Could not parse cashbook content', previous: $e);
		}
		return $this->cashbook;
	}

	protected function iterateReport() {
		while ($this->reportIterator->valid()) {
			$record = $this->reportIterator->current();
			$opening = $this->parseDateTime($record['opening']);
			$openingBalance = $this->parseMoney($record['balanceStart'], $this->cashbook->getCurrency());
			$closing = $this->parseDateTime($record['closing']);
			$closingBalance = $this->parseMoney($record['balanceEnd'], $this->cashbook->getCurrency());

			$startEntry = Entry::create(
				$this->cashbook,
				$this->userId,
				$opening,
				'',
				EntryReason::DayOpening,
				Money::of('0', $this->cashbook->getCurrency()),
				$openingBalance,
			);

			$this->entryMapper->insert($startEntry);

			$lastEntry = $this->iterateCashbook($closing);
			if (($lastEntry === null && !$closingBalance->isEqualTo($openingBalance))
				|| ($lastEntry !== null && !$lastEntry->getBalance()->isEqualTo($closingBalance))) {
				$this->logger->error('Mismatch between closing balance and cashbook balance', [
					'opening' => $opening->format('Y-m-d H:i:s'),
					'closingBalance' => (string)$closingBalance,
					'lastEntryBalance' => $lastEntry !== null ? (string)$lastEntry->getBalance() : 'null',
					'openingBalance' => (string)$openingBalance,
				]);
				throw new InvalidArgumentException('Mismatch between closing balance and cashbook balance');
			}

			$endEntry = Entry::create(
				$this->cashbook,
				$this->userId,
				$closing,
				'',
				EntryReason::DayClosing,
				Money::of('0', $this->cashbook->getCurrency()),
				$closingBalance,
				reference: $record['znumber'],
			);
			$this->entryMapper->insert($endEntry);

			$this->reportIterator->next();
		}

	}

	/**
	 *
	 * @param Iterator<string|int, array<string|int, mixed>>& $cashbookIterator
	 */
	protected function iterateCashbook(?\DateTimeImmutable $limit = null): ?Entry {
		while ($this->cashbookIterator->valid()) {
			$record = $this->cashbookIterator->current();
			$datetime = $this->parseDateTime($record['datetime']);

			if (($limit !== null && $datetime >= $limit)) {
				break;
			}

			$amount = $this->parseMoney($record['debit'] ?: '0', $this->cashbook->getCurrency())
				->minus($this->parseMoney($record['credit'] ?: '0', $this->cashbook->getCurrency())->abs());
			$total = $this->parseMoney($record['total'], $this->cashbook->getCurrency());

			$entry = Entry::create(
				$this->cashbook,
				$this->userId,
				$this->parseDateTime($record['datetime']),
				$record['text'],
				$this->mapReason($record['reason']),
				$amount,
				$total,
				BigRational::of($this->normalizeNumber($record['tax']))->dividedBy('100'),
			);
			$this->entryMapper->insert($entry);

			$this->cashbookIterator->next();
		}
		return $entry ?? null;
	}

	/**
	 * Normalize the number format of strings (replace decimal comma with dot).
	 */
	protected function normalizeNumber(string $number): string {
		return str_replace(',', '.', $number);
	}

	/**
	 * Get the Money value of a CSV string value.
	 */
	protected function parseMoney(string $value): Money {
		return Money::of($this->normalizeNumber($value), $this->cashbook->getCurrency());
	}

	protected function parseDateTime(string $value): \DateTimeImmutable {
		$parsed = new \DateTimeImmutable($value, $this->timezone);
		return $parsed->setTimezone(new \DateTimeZone('UTC'));
	}

	/**
	 * Map localized CSV header to english keys.
	 */
	protected function mapCashbookCsvHeader(array $headers): array {
		return array_map(fn (string $name) => self::CSV_HEADER_MAPPING[$name] ?? $name, $headers);
	}

	/**
	 * Map localized CSV header to english keys.
	 */
	protected function mapReportCsvHeader(array $header): array {
		return array_map(fn (string $name) => self::CSV_REPORT_HEADER_MAPPING[$name] ?? $name, $header);
	}

	/**
	 * Mapping from string value of the CSV to the `EntryReason` enum.
	 *
	 * @psalm-return EntryReason::*|null
	 */
	protected function mapReason(string $reason): ?string {
		return match($reason) {
			'Einkauf von Material und Waren' => EntryReason::ExpenseGoods,
			'Trinkgeldauszahlung' => EntryReason::ExpenseTips,
			'Erstattung' => EntryReason::ExpenseRefund,
			'Wechselgeld' => EntryReason::ExpenseChange,
			'Andere Ausgaben' => EntryReason::ExpenseMisc,
			'Gehälter / Löhne' => EntryReason::ExpenseSalary,

			'Andere Einnahmen' => EntryReason::IncomeMisc,
			'Sonstige Einnahme' => EntryReason::IncomeSales,

			'Transfer von Kasse zur Bank' => EntryReason::ExpenseBankTransfer,
			'Transfer von Bank zur Kasse' => EntryReason::IncomeBankTransfer,

			'Änderungseintrag' => EntryReason::Correction,

			default => null,
		};
	}
}
