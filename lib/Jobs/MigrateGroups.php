<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Jobs;

use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
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

	/** @var IConfig */
	private $config;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IDBConnection */
	private $dbc;
	/** @var GroupBackend */
	private $ownGroupBackend;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		IDBConnection $dbc,
		GroupBackend $ownGroupBackend,
		LoggerInterface $logger,
		ITimeFactory $timeFactory
	) {
		parent::__construct($timeFactory);
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
		$candidateInfo = $this->config->getAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === null || $candidateInfo === '') {
			return;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || !isset($candidateInfo['groups'])) {
			return;
		}
		$candidateInfo['groups'] = array_diff($candidateInfo['groups'], $migrateGroups);
		$this->config->setAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			json_encode($candidateInfo)
		);
	}

	protected function migrateGroups(array $toMigrate): array {
		return array_filter($toMigrate, function ($gid) {
			return $this->migrateGroup($gid);
		});
	}

	protected function migrateGroup(string $gid): bool {
		try {
			$this->dbc->beginTransaction();

			$qb = $this->dbc->getQueryBuilder();
			$affected = $qb->delete('groups')
				->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
				->executeStatement();
			if ($affected === 0) {
				throw new \RuntimeException('Could not delete group from local backend');
			}

			if (!$this->ownGroupBackend->createGroup($gid)) {
				throw new \RuntimeException('Could not create group in SAML backend');
			}

			$this->dbc->commit();

			return true;
		} catch (\Exception $e) {
			$this->dbc->rollBack();
			$this->logger->warning($e->getMessage(), ['app' => 'user_saml', 'exception' => $e]);
		}

		return false;
	}

	protected function getGroupsToMigrate(array $samlGroups, array $pool): array {
		return array_filter($samlGroups, function (string $gid) use ($pool) {
			if (!in_array($gid, $pool)) {
				return false;
			}

			$group = $this->groupManager->get($gid);
			if ($group === null) {
				return false;
			}

			$backendNames = $group->getBackendNames();
			if (!in_array('Database', $backendNames, true)) {
				return false;
			}

			foreach ($group->getUsers() as $user) {
				if ($user->getBackendClassName() !== 'user_saml') {
					return false;
				}
			}

			return true;
		});
	}

	protected function getMigratableGroups(): array {
		$candidateInfo = $this->config->getAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === null || $candidateInfo === '') {
			throw new \RuntimeException('No migration of groups to SAML backend anymore');
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || !isset($candidateInfo['groups']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->deleteAppValue('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
			throw new \RuntimeException('Period for migration groups is over');
		}

		return $candidateInfo['groups'];
	}
}
