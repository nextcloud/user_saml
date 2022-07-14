<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\User_SAML\Migration;

use OC\Group\Database;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\UserBackend;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use UnexpectedValueException;
use function json_encode;

class RememberLocalGroupsForPotentialMigrations implements IRepairStep {

	/** @var IGroupManager */
	private $groupManager;
	/** @var IConfig */
	private $config;
	/** @var UserBackend */
	private $ownUserBackend;

	public function __construct(
		IGroupManager $groupManager,
		IConfig $config,
		UserBackend $ownUserBackend
	) {
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->ownUserBackend = $ownUserBackend;
	}

	public function getName(): string {
		return 'Remember local groups that might belong to SAML';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 * @since 9.1.0
	 */
	public function run(IOutput $output) {
		try {
			$backend = $this->findBackend();
			$groupIds = $this->findGroupIds($backend);
		} catch (UnexpectedValueException $e) {
			return;
		}

		$this->config->setAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			json_encode([
				'dropAfter' => time() + 60 * 24 * 60 * 60,
				'groups' => $groupIds
			])
		);
	}

	protected function findGroupIds(Database $backend): array {
		$groupIds = $backend->getGroups();
		$adminGroupIndex = array_search('admin', $groupIds, true);
		if ($adminGroupIndex !== false) {
			unset($groupIds[$adminGroupIndex]);
		}
		return $groupIds;
	}

	protected function findBackend(): Database {
		$groupBackends = $this->groupManager->getBackends();
		foreach ($groupBackends as $backend) {
			if ($backend instanceof Database) {
				return $backend;
				break;
			}
		}
		throw new UnexpectedValueException();
	}
}
