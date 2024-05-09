<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;

class TimezoneController extends Controller {

	/** @var IConfig */
	private $config;
	/** @var string */
	private $userId;
	/** @var ISession */
	private $session;

	public function __construct($appName,
		IRequest $request,
		IConfig $config,
		$userId,
		ISession $session) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userId = $userId;
		$this->session = $session;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function setTimezone(string $timezone, int $timezoneOffset): JSONResponse {
		$this->config->setUserValue($this->userId, 'core', 'timezone', $timezone);
		$this->session->set('timezone', $timezoneOffset);

		return new JSONResponse();
	}
}
