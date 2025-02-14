<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Cashbook\Controller;

use OCA\Cashbook\Db\CashbookMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

class PageController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		protected ?string $userId,
		protected IInitialState $initialState,
		protected IURLGenerator $urlGenerator,
		protected CashbookMapper $cashbookMapper,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired()]
	#[NoCSRFRequired()]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		Util::addScript($this->appName, 'cashbook-main');
		Util::addStyle($this->appName, 'cashbook-main');
		
		return new TemplateResponse($this->appName, 'main');
	}

	#[NoAdminRequired()]
	#[NoCSRFRequired()]
	#[FrontpageRoute(verb: 'GET', url: '/{cashbookId}', requirements: ['cashbookId' => '\d+'])]
	public function showCashbook(int $cashbookId): TemplateResponse {
		try {
			$cashbook = $this->cashbookMapper->getCashbook($cashbookId);
			if ($cashbook->getOwner() !== $this->userId) {
				throw new DoesNotExistException('');
			}

			$this->initialState->provideInitialState('cashbook', $cashbook);
			return $this->index();
		} catch (DoesNotExistException) {
			// Cashbook does not exist so redirect to index below
		}
		return new RedirectResponse(
			$this->urlGenerator->linkToRouteAbsolute('cashbook.page.index')
		);
	}

	#[NoAdminRequired()]
	#[NoCSRFRequired()]
	#[FrontpageRoute(verb: 'GET', url: '/{cashbookId}/export', requirements: ['cashbookId' => '\d+'])]
	public function exportCashbook(int $cashbookId): TemplateResponse {
		Util::addScript($this->appName, 'cashbook-export');
		Util::addStyle($this->appName, 'cashbook-export');
		
		return new TemplateResponse($this->appName, 'export', renderAs: TemplateResponse::RENDER_AS_BASE);
	}
}
