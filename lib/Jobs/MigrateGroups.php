<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Jobs;

use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\Service\GroupMigration;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

/**
 * Class MigrateGroups
 *
 * @package OCA\User_SAML\Jobs
 * @todo: remove this, when dropping Nextcloud 29 support
 */
class MigrateGroups extends QueuedJob {
	use TTransactional;

	protected const BATCH_SIZE = 1000;

	public function __construct(
		protected GroupMigration $groupMigration,
		protected GroupManager $ownGroupManager,
		private IConfig $config,
		private IGroupManager $groupManager,
		private IDBConnection $dbc,
		private GroupBackend $ownGroupBackend,
		private LoggerInterface $logger,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
	}

	#[\Override]
	protected function run($argument) {
		try {
			$candidates = $this->getMigratableGroups();
			$toMigrate = $this->getGroupsToMigrate($argument['gids'], $candidates);
			$migrated = $this->migrateGroups($toMigrate);
			$this->ownGroupManager->updateCandidatePool($migrated);
		} catch (\RuntimeException) {
			return;
		}
	}

	protected function migrateGroups(array $toMigrate): array {
		return array_filter($toMigrate, fn ($gid) => $this->groupMigration->migrateGroup($gid));
	}

	protected function getGroupsToMigrate(array $samlGroups, array $pool): array {
		return array_filter($samlGroups, function (string $gid) use ($pool) {
			if (!in_array($gid, $pool)) {
				return false;
			}

			$group = $this->groupManager->get($gid);
			if ($group === null) {
				$this->logger->debug('Not migrating group "{gid}": not found by the group manager', [
					'app' => 'user_saml',
					'gid' => $gid,
				]);
				return false;
			}

			$backendNames = $group->getBackendNames();
			if (!in_array('Database', $backendNames, true)) {
				$this->logger->debug('Not migrating group "{gid}": not belonging to local database backend', [
					'app' => 'user_saml',
					'gid' => $gid,
					'backends' => $backendNames,
				]);
				return false;
			}

			foreach ($group->getUsers() as $user) {
				if ($user->getBackendClassName() !== 'user_saml') {
					$this->logger->debug('Not migrating group "{gid}": user "{userId}" from a different backend "{userBackend}"', [
						'app' => 'user_saml',
						'gid' => $gid,
						'userId' => $user->getUID(),
						'userBackend' => $user->getBackendClassName(),
					]);
					return false;
				}
			}

			return true;
		});
	}

	protected function getMigratableGroups(): array {
		$candidateInfo = $this->ownGroupManager->getCandidateInfoIfValid();
		if ($candidateInfo === null) {
			throw new \RuntimeException('No migration tasks of groups to SAML backend');
		}

		return $candidateInfo['groups'];
	}
}
