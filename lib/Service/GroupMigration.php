<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Service;

use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

		// Remove prefix from group names
		$allOwnedGroups = array_merge($allOwnedGroups, array_map(function (string $groupName): string {
			if (substr($groupName, 0, strlen(SAMLSettings::DEFAULT_GROUP_PREFIX)) == SAMLSettings::DEFAULT_GROUP_PREFIX) {
				$groupName = substr($groupName, strlen(SAMLSettings::DEFAULT_GROUP_PREFIX));
			}
			return $groupName;
		}, $allOwnedGroups));

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
	public function migrateGroupUsers(string $gid, ?OutputInterface $output = null, bool $dryRun = false): bool {
		$originalGroup = $this->groupManager->get($gid);
		$members = $originalGroup?->getUsers();

		$newGid = $gid;
		if (!$this->ownGroupBackend->groupExists($gid)) {
			if ($this->ownGroupBackend->groupExists(SAMLSettings::DEFAULT_GROUP_PREFIX . $gid)) {
				$newGid = SAMLSettings::DEFAULT_GROUP_PREFIX . $gid;
			} else {
				$output->writeln("SAML group corresponding to the local $gid group does not exist");
				return true;
			}
		}

		if ($dryRun) {
			assert($output instanceof OutputInterface);
			$output->writeln('Found ' . count($members) . ' members in old local group ' . $gid . ' and migrating them to ' . $newGid);
			return true;
		}

		$areAllInserted = true;
		foreach (array_chunk($members ?? [], (int)floor(self::CHUNK_SIZE / 2)) as $userBatch) {
			$areAllInserted = ($this->atomic(function () use ($userBatch, $newGid) {
				/** @var IUser $user */
				foreach ($userBatch as $user) {
					$this->dbc->insertIgnoreConflict(
						GroupBackend::TABLE_MEMBERS,
						[
							'gid' => $newGid,
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
