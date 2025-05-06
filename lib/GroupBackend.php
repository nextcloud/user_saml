<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OCP\DB\Exception;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\IDBConnection;
use PDO;
use Psr\Log\LoggerInterface;

class GroupBackend extends ABackend implements IAddToGroupBackend, ICountUsersBackend, ICreateGroupBackend, IDeleteGroupBackend, IGetDisplayNameBackend, IRemoveFromGroupBackend, ISetDisplayNameBackend, INamedBackend {

	/** @var array */
	private $groupCache = [];

	public const TABLE_GROUPS = 'user_saml_groups';
	public const TABLE_MEMBERS = 'user_saml_group_members';

	public function __construct(
		protected IDBConnection $dbc,
		protected LoggerInterface $logger,
	) {
	}

	public function inGroup($uid, $gid): bool {
		$qb = $this->dbc->getQueryBuilder();
		$stmt = $qb->select('gid')
			->from(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->setMaxResults(1)
			->executeQuery();

		$result = count($stmt->fetchAll()) > 0;
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @return list<string> Group names
	 */
	public function getUserGroups($uid): array {
		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid')
			->from(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$groups = [];
		while ($row = $cursor->fetch()) {
			$groups[] = $row['gid'];
		}
		$cursor->closeCursor();

		return $groups;
	}

	/**
	 * @return string[] Group names
	 */
	public function getGroups($search = '', $limit = null, $offset = null): array {
		$query = $this->dbc->getQueryBuilder();
		$query->select('gid', 'displayname')
			->from(self::TABLE_GROUPS)
			->orderBy('gid', 'ASC');

		if ($search !== '') {
			$query->where($query->expr()->iLike('gid', $query->createNamedParameter(
				'%' . $this->dbc->escapeLikeParameter($search) . '%'
			)));
			$query->orWhere($query->expr()->iLike('displayname', $query->createNamedParameter(
				'%' . $this->dbc->escapeLikeParameter($search) . '%'
			)));
		}

		if ((int)$limit > 0) {
			$query->setMaxResults((int)$limit);
		}
		if ((int)$offset > 0) {
			$query->setFirstResult((int)$offset);
		}
		$result = $query->executeQuery();

		$groups = [];
		while ($row = $result->fetch()) {
			$groups[] = $row['gid'];
			$this->groupCache[$row['gid']] = $row['displayname'];
		}
		$result->closeCursor();

		return $groups;
	}

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid): bool {
		if (isset($this->groupCache[$gid])) {
			return true;
		}

		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid', 'displayname')
			->from(self::TABLE_GROUPS)
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->setMaxResults(1)
			->executeQuery();
		$result = $cursor->fetch();
		$cursor->closeCursor();

		if ($result !== false) {
			$this->groupCache[$gid] = $result['displayname'];
			return true;
		}
		return false;
	}

	public function groupExistsWithDifferentGid(string $samlGid): ?string {
		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid')
			->from(self::TABLE_GROUPS)
			->where($qb->expr()->eq('saml_gid', $qb->createNamedParameter($samlGid)))
			->executeQuery();
		$result = $cursor->fetch(PDO::FETCH_NUM);
		$cursor->closeCursor();

		if ($result !== false) {
			return $result[0];
		}
		return null;
	}

	/**
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array<int,string> User ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		$query = $this->dbc->getQueryBuilder();
		$query->select('uid')
			->from(self::TABLE_MEMBERS)
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->orderBy('uid', 'ASC');

		if ($search !== '') {
			$query->andWhere($query->expr()->like('uid', $query->createNamedParameter(
				'%' . $this->dbc->escapeLikeParameter($search) . '%'
			)));
		}

		if ($limit !== -1) {
			$query->setMaxResults($limit);
		}
		if ($offset !== 0) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();

		$users = [];
		while ($row = $result->fetch()) {
			$users[] = $row['uid'];
		}
		$result->closeCursor();

		return $users;
	}

	public function createGroup(string $gid, ?string $samlGid = null): bool {
		try {
			// Add group
			$builder = $this->dbc->getQueryBuilder();
			$samlGid ??= $gid;
			$result = $builder->insert(self::TABLE_GROUPS)
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($samlGid))
				->setValue('saml_gid', $builder->createNamedParameter($samlGid))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$result = 0;
			} else {
				$this->logger->warning('Failed to create group: ' . $e->getMessage(), [
					'app' => 'user_saml',
					'exception' => $e,
				]);
				$result = -1;
			}
		}

		// Add to cache
		$this->groupCache[$gid] = $samlGid;

		return $result === 1;
	}

	/**
	 * @throws Exception
	 */
	public function addToGroup(string $uid, string $gid): bool {
		if ($this->inGroup($uid, $gid)) {
			return true;
		}

		$qb = $this->dbc->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERS)
			->setValue('uid', $qb->createNamedParameter($uid))
			->setValue('gid', $qb->createNamedParameter($gid))
			->executeStatement();
		return true;
	}

	public function removeFromGroup(string $uid, string $gid): bool {
		$qb = $this->dbc->getQueryBuilder();
		$rows = $qb->delete(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		return $rows > 0;
	}

	public function countUsersInGroup(string $gid, string $search = ''): int {
		$query = $this->dbc->getQueryBuilder();
		$query->select($query->func()->count('*', 'num_users'))
			->from(self::TABLE_MEMBERS)
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));

		if ($search !== '') {
			$query->andWhere($query->expr()->like('uid', $query->createNamedParameter(
				'%' . $this->dbc->escapeLikeParameter($search) . '%'
			)));
		}

		$result = $query->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	public function deleteGroup(string $gid): bool {
		$query = $this->dbc->getQueryBuilder();

		try {
			$this->dbc->beginTransaction();

			// delete the group
			$query->delete(self::TABLE_GROUPS)
				->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
				->executeStatement();

			// delete group user relation
			$query->delete(self::TABLE_MEMBERS)
				->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
				->executeStatement();

			$this->dbc->commit();
			unset($this->groupCache[$gid]);
		} catch (\Throwable $t) {
			$this->dbc->rollBack();
			throw $t;
		}

		return true;
	}

	public function getBackendName(): string {
		return 'user_saml';
	}

	public function getDisplayName(string $gid): string {
		if (!isset($this->groupCache[$gid])) {
			$this->getGroups($gid);
		}

		return $this->groupCache[$gid] ?? $gid;
	}

	public function setDisplayName(string $gid, string $displayName): bool {
		if (!$this->groupExists($gid)) {
			return false;
		}

		$displayName = trim($displayName);
		if ($displayName === '') {
			$displayName = $gid;
		}

		$query = $this->dbc->getQueryBuilder();
		$isUpdated = $query->update(self::TABLE_GROUPS)
			->set('displayname', $query->createNamedParameter($displayName))
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->executeStatement() > 0;

		if ($isUpdated) {
			$this->groupCache[$gid] = $displayName;
		}

		return $isUpdated;
	}
}
