<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Service;

use OCA\User_SAML\GroupBackend;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Throwable;

class GroupMigration {
	use TTransactional;

	protected const CHUNK_SIZE = 1000;

	public function __construct(
		protected GroupBackend $ownGroupBackend,
		protected IGroupManager $groupManager,
		protected IDBConnection $dbc,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * @return string[] group ids
	 */
	public function findGroupsWithLocalMembers(): array {
		$foundGroups = [];

		$qb = $this->dbc->getQueryBuilder();
		$qb->selectDistinct('gid')
			->from('group_user')
			->where($qb->expr()->in('gid', $qb->createParameter('gidList')));

		$allOwnedGroups = $this->ownGroupBackend->getGroups();
		foreach (array_chunk($allOwnedGroups, self::CHUNK_SIZE) as $groupsChunk) {
			$qb->setParameter('gidList', $groupsChunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $qb->executeQuery();
			while ($gid = $result->fetchOne()) {
				$foundGroups[] = $gid;
			}
			$result->closeCursor();
		}

		return $foundGroups;
	}

	/**
	 * @returns bool true when all users were migrated, when they were only partly migrated
	 * @throws Exception
	 * @throws Throwable
	 */
	public function migrateGroupUsers(string $gid): bool {
		$originalGroup = $this->groupManager->get($gid);
		$members = $originalGroup?->getUsers();

		$areAllInserted = true;
		foreach (array_chunk($members ?? [], (int)floor(self::CHUNK_SIZE / 2)) as $userBatch) {
			$areAllInserted = ($this->atomic(function () use ($userBatch, $gid) {
				/** @var IUser $user */
				foreach ($userBatch as $user) {
					$this->dbc->insertIgnoreConflict(
						GroupBackend::TABLE_MEMBERS,
						[
							'gid' => $gid,
							'uid' => $user->getUID(),
						]
					);
				}
				return true;
			}, $this->dbc) === true) && $areAllInserted;
		}
		if (!$areAllInserted) {
			$this->logger->warning('Partial migration of users from local group {gid} to SAML.', [
				'app' => 'user_saml',
				'gid' => $gid,
			]);
		}
		return $areAllInserted;
	}

	/**
	 * @throws Exception
	 */
	public function cleanUpOldGroupUsers(string $gid): void {
		$cleanup = $this->dbc->getQueryBuilder();
		$cleanup->delete('group_user')
			->where($cleanup->expr()->eq('gid', $cleanup->createNamedParameter($gid)));
		$cleanup->executeStatement();
	}

}
