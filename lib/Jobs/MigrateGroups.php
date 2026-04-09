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
	protected function run(mixed $argument): void {
		try {
			$candidates = $this->getMigratableGroups();
			$toMigrate = $this->groupMigration->getGroupsToMigrate($argument['gids'], $candidates);
			$migrated = $this->migrateGroups($toMigrate);
			$this->ownGroupManager->updateCandidatePool($migrated);
		} catch (\RuntimeException) {
			return;
		}
	}

	protected function migrateGroups(array $toMigrate): array {
		return array_filter($toMigrate, fn ($gid) => $this->groupMigration->migrateGroup($gid));
	}

	protected function getMigratableGroups(): array {
		$candidateInfo = $this->ownGroupManager->getCandidateInfoIfValid();
		if ($candidateInfo === null) {
			throw new \RuntimeException('No migration tasks of groups to SAML backend');
		}

		return $candidateInfo['groups'];
	}
}
