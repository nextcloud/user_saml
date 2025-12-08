<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;

class TimezoneController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IConfig $config,
		private ?string $userId,
		private readonly ISession $session,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 * @throws \InvalidArgumentException
	 */
	#[NoAdminRequired]
	#[UseSession]
	public function setTimezone(string $timezone, int $timezoneOffset): JSONResponse {
		if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
			throw new \InvalidArgumentException('Invalid timezone');
		}
		if ($this->userId === null) {
			throw new \RuntimeException('Unable to set timezone for null user');
		}
		$this->config->setUserValue($this->userId, 'core', 'timezone', $timezone);
		$this->session->set('timezone', $timezoneOffset);

		return new JSONResponse();
	}
}
