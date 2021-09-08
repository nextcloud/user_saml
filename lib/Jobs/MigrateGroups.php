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

namespace OCA\User_SAML\Jobs;

use OC\BackgroundJob\QueuedJob;
use OC\Group\Database;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ILogger;

/**
 * Class MigrateGroups
 *
 * @package OCA\User_SAML\Jobs
 * @todo: remove this, when dropping Nextcloud 18 support
 */
class MigrateGroups extends QueuedJob {

	/** @var IConfig */
	private $config;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IDBConnection */
	private $dbc;
	/** @var GroupBackend */
	private $ownGroupBackend;
	/** @var ILogger */
	private $logger;

	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		IDBConnection $dbc,
		GroupBackend $ownGroupBackend,
		ILogger $logger
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->dbc = $dbc;
		$this->ownGroupBackend = $ownGroupBackend;
		$this->logger = $logger;
	}

	protected function run($argument) {
		try {
			$candidates = $this->getMigratableGroups();
			$toMigrate = $this->getGroupsToMigrate($argument['gids'], $candidates);
			$migrated = $this->migrateGroups($toMigrate);
			$this->updateCandidatePool($migrated);
		} catch (\RuntimeException $e) {
			return;
		}
	}

	protected function updateCandidatePool($migrateGroups) {
		$candidateInfo = $this->config->getAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, null);
		if($candidateInfo === null) {
			return;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if(!isset($candidateInfo['dropAfter']) || !isset($candidateInfo['groups'])) {
			return;
		}
		$candidateInfo['groups'] = array_diff($candidateInfo['groups'], $migrateGroups);
		$this->config->setAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			json_encode($candidateInfo)
		);
	}

	protected function migrateGroups(array $toMigrate) {
		return array_filter($toMigrate, function ($gid) {
			return $this->migrateGroup($gid);
		});
	}

	protected function migrateGroup(string $gid) {
		try {
			$this->dbc->beginTransaction();

			$qb = $this->dbc->getQueryBuilder();
			$affected = $qb->delete('groups')
				->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
				->execute();
			if($affected === 0) {
				throw new \RuntimeException('Could not delete group from local backend');
			}

			if(!$this->ownGroupBackend->createGroup($gid)) {
				throw new \RuntimeException('Could not create group in SAML backend');
			}

			$this->dbc->commit();

			return true;
		} catch (\Exception $e) {
			$this->dbc->rollBack();
			$this->logger->logException($e, ['app' => 'user_saml', 'level' => ILogger::WARN]);
		}

		return false;
	}

	protected function getGroupsToMigrate(array $samlGroups, array $pool) {
		return array_filter($samlGroups, function (string $gid) use ($pool) {
			if(!in_array($gid, $pool)) {
				return false;
			}

			$group = $this->groupManager->get($gid);
			if($group === null) {
				return false;
			}
			$reflected = new \ReflectionClass($group);
			$backendsProperty = $reflected->getProperty('backends');
			$backendsProperty->setAccessible(true);
			$backends = $backendsProperty->getValue($group);
			foreach ($backends as $backend) {
				if($backend instanceof Database) {
					return true;
				}
			}
			return false;
		});
	}

	protected function getMigratableGroups(): array {
		$candidateInfo = $this->config->getAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, null);
		if($candidateInfo === null) {
			throw new \RuntimeException('No migration of groups to SAML backend anymore');
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if(!isset($candidateInfo['dropAfter']) || !isset($candidateInfo['groups']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->deleteAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
			throw new \RuntimeException('Period for migration groups is over');
		}

		return $candidateInfo['groups'];
	}


}
