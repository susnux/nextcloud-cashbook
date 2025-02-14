<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $value)
 * @method string getAccount()
 * @method void setAccount(string $value)
 * @method string getCurrency()
 * @method void setCurrency(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 */
class Cashbook extends Entity implements \JsonSerializable {

	/**
	 * Name of the cashbook
	 * @var string
	 */
	protected $name;

	/**
	 * Account this cashbook is about
	 * @var string
	 */
	protected $account;

	/**
	 * The currency (ISO code) this cashbook is kept
	 * @var string
	 */
	protected $currency;

	/**
	 * The owner of the cashbook
	 * @var string
	 */
	protected $owner;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('account', 'string');
		$this->addType('currency', 'string');
		$this->addType('owner', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'account' => $this->account,
			'currency' => $this->currency,
			'name' => $this->name,
			'owner' => $this->owner,
		];
	}
}
