<?php
/**
 * @copyright Copyright (c) 2018 Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
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

namespace OCA\User_SAML\Strategies\Authentication;

use OCP\IConfig;
use OCP\ILogger;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserBackend;

interface StrategyInterface {
	/**
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param IUserManager $userManager
	 * @param UserBackend $userBackend
	 * @param ISession $session
	 * @return Http\RedirectResponse
	 * @throws \Exception
	 */
	public function login(IConfig $config, IURLGenerator $urlGenerator, ILogger $logger, IUserManager $userManager, IUserBackend $userBackend, ISession $session);
}
