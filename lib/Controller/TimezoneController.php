<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	 *
	 * @param string $timezone
	 * @param int $timezoneOffset
	 * @return JSONResponse
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function setTimezone($timezone, $timezoneOffset) {
		$this->config->setUserValue($this->userId, 'core', 'timezone', $timezone);
		$this->session->set('timezone', $timezoneOffset);

		return new JSONResponse();
	}
}
