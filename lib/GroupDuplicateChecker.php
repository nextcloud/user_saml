<?php
/**
 * @copyright Copyright (c) 2019 Dominik Ach <da@infodatacom.de>
 *
 * @author Dominik Ach <da@infodatacom.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Maximilian Ruta <mr@xtain.net>
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Giuliano Mele <giuliano.mele@verdigado.com>
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

namespace OCA\User_SAML;

use OCP\IConfig;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

class GroupDuplicateChecker
{
	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @var IGroupManager
	 */
	protected $groupManager;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		LoggerInterface $logger
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	public function checkForDuplicates(string $group): void {
		$existingGroup = $this->groupManager->get($group);
		if ($existingGroup !== null) {
			$reflection = new \ReflectionClass($existingGroup);
			$property = $reflection->getProperty('backends');
			$property->setAccessible(true);
			$backends = $property->getValue($existingGroup);
			if ($backends) {
				foreach ($backends as $backend) {
					if ($backend instanceof GroupBackend) {
						return;
					}
				}
			}

			$this->logger->warning(
				'Group {name} already existing in other backend',
				[
					'name' => $group
				]
			);
		}
	}
}
