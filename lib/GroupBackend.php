<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OC\User\LazyUser;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PDO;
use Psr\Log\LoggerInterface;

class GroupBackend extends ABackend implements
	IAddToGroupBackend,
	ICountUsersBackend,
	ICreateNamedGroupBackend,
	IDeleteGroupBackend,
	IGetDisplayNameBackend,
	IRemoveFromGroupBackend,
	ISetDisplayNameBackend,
	INamedBackend,
	IBatchMethodsBackend,
	IGroupDetailsBackend,
	ISearchableGroupBackend {

	/** @var array<string, string|null> */
	private array $groupCache = [];

	protected ?UserBackend $userBackend = null;

	public const TABLE_GROUPS = 'user_saml_groups';
	public const TABLE_MEMBERS = 'user_saml_group_members';

	public function __construct(
		protected IDBConnection $dbc,
		protected LoggerInterface $logger,
	) {
	}

	private function getUserBackend(): UserBackend {
		return $this->userBackend ?? Server::get(UserBackend::class);
	}

	#[\Override]
	public function inGroup($uid, $gid): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group membership
			return false;
		}
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
	 * @param string $uid
	 * @return list<string> Group names
	 */
	#[\Override]
	public function getUserGroups($uid): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for the list of groups
			return [];
		}
		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gu.gid', 'g.displayname')
			->from(self::TABLE_MEMBERS, 'gu')
			->leftJoin('gu', self::TABLE_GROUPS, 'g', $qb->expr()->eq('gu.gid', 'g.gid'))
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$groups = [];
		while ($row = $cursor->fetch()) {
			$gid = $row['gid'];
			$this->groupCache[$gid] = $row['displayname'];
			$groups[] = $gid;
		}
		$cursor->closeCursor();

		return $groups;
	}

	/**
	 * @return string[] Group names
	 */
	#[\Override]
	public function getGroups(string $search = '', ?int $limit = null, ?int $offset = null): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for the list of groups
			return [];
		}
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

	#[\Override]
	public function groupExists($gid): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for the list of groups
			return false;
		}

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

	#[\Override]
	public function groupsExists(array $gids): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for the list of groups
			return [];
		}
		$notFoundGids = [];
		$existingGroups = [];

		// In case the data is already locally accessible, not need to do SQL query
		// or do a SQL query but with a smaller in clause
		foreach ($gids as $gid) {
			if (isset($this->groupCache[$gid])) {
				$existingGroups[] = $gid;
			} else {
				$notFoundGids[] = $gid;
			}
		}

		foreach (array_chunk($notFoundGids, 1000) as $chunk) {
			$query = $this->dbc->getQueryBuilder();
			$query->select('gid', 'displayname')
				->from(self::TABLE_GROUPS)
				->where($query->expr()->in('gid', $query->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $query->executeQuery();
			while ($row = $result->fetch()) {
				$gid = (string)$row['gid'];
				$existingGroups[] = $gid;
				$this->groupCache[$gid] = (string)$row['displayname'];
			}
			$result->closeCursor();
		}

		return $existingGroups;
	}

	public function groupExistsWithDifferentGid(string $samlGid): ?string {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for the list of groups
			return null;
		}
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
	#[\Override]
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group membership
			return [];
		}

		$query = $this->dbc->getQueryBuilder();
		$query->selectDistinct('m.uid')
			->from(self::TABLE_MEMBERS, 'm')
			->where($query->expr()->eq('m.gid', $query->createNamedParameter($gid)))
			->orderBy('m.uid', 'ASC');

		$this->setupSearchQuery($search, $query);

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

	/**
	 * Compute group ID from display name (GIDs are limited to 64 characters in database)
	 */
	private function computeGid(string $displayName): string {
		return mb_strlen($displayName) > 64
			? hash('sha256', $displayName)
			: $displayName;
	}

	#[\Override]
	public function createGroup(string $name, ?string $samlGid = null): ?string {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We create groups on the auto-provisioning backend
			return null;
		}

		$gid = $this->computeGid($name);

		try {
			// Add group
			$builder = $this->dbc->getQueryBuilder();
			$samlGid ??= $name;
			$builder->insert(self::TABLE_GROUPS)
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($samlGid))
				->setValue('saml_gid', $builder->createNamedParameter($samlGid))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$this->logger->warning('Failed to create group: ' . $e->getMessage(), [
					'app' => 'user_saml',
					'exception' => $e,
				]);
				return null;
			}
		}

		// Add to cache
		$this->groupCache[$gid] = $samlGid;

		return $gid;
	}

	/**
	 * @throws Exception
	 */
	#[\Override]
	public function addToGroup(string $uid, string $gid): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We add user to groups on the auto-provisioning backend
			return false;
		}

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

	#[\Override]
	public function removeFromGroup(string $uid, string $gid): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We remove user to groups on the auto-provisioning backend
			return false;
		}

		$qb = $this->dbc->getQueryBuilder();
		$rows = $qb->delete(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		return $rows > 0;
	}

	#[\Override]
	public function countUsersInGroup(string $gid, string $search = ''): int {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group membership
			return 0;
		}

		$query = $this->dbc->getQueryBuilder();
		$query->select(
			$query->createFunction('COUNT(DISTINCT ' . $query->getColumnName('uid', 'm') . ')')
		)
			->from(self::TABLE_MEMBERS, 'm')
			->where($query->expr()->eq('m.gid', $query->createNamedParameter($gid)));

		$this->setupSearchQuery($search, $query);

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

	#[\Override]
	public function deleteGroup(string $gid): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group management
			return false;
		}

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

	#[\Override]
	public function getBackendName(): string {
		return 'user_saml';
	}

	#[\Override]
	public function getDisplayName(string $gid): string {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group management
			return '';
		}

		if (!isset($this->groupCache[$gid])) {
			$this->getGroups($gid);
		}

		return $this->groupCache[$gid] ?? $gid;
	}

	#[\Override]
	public function setDisplayName(string $gid, string $displayName): bool {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group management
			return false;
		}

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

	#[\Override]
	public function getGroupDetails(string $gid): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group management
			return [];
		}

		if (!$this->groupExists($gid)) {
			return [];
		}

		return ['displayName' => $this->groupCache[$gid] ?? $gid];
	}

	#[\Override]
	public function getGroupsDetails(array $gids): array {
		if (!$this->getUserBackend()->autoprovisionAllowed()) {
			// We rely on the auto-provisioning backend for group management
			return [];
		}

		$notFoundGids = [];
		$details = [];

		// Try to skip groups already in local cache
		foreach ($gids as $gid) {
			if (isset($this->groupCache[$gid])) {
				$details[$gid] = ['displayName' => $this->groupCache[$gid] ?? $gid];
			} else {
				$notFoundGids[] = $gid;
			}
		}

		foreach (array_chunk($notFoundGids, 1000) as $chunk) {
			$query = $this->dbc->getQueryBuilder();
			$query->select('gid', 'displayname')
				->from(self::TABLE_GROUPS)
				->where($query->expr()->in('gid', $query->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $query->executeQuery();
			while ($row = $result->fetch()) {
				$details[(string)$row['gid']] = ['displayName' => (string)$row['displayname']];
				$this->groupCache[(string)$row['gid']] = (string)$row['displayname'];
			}
			$result->closeCursor();
		}

		return $details;
	}

	public function setupSearchQuery(string $search, IQueryBuilder $query, bool $includeMetadata = false): void {
		if ($includeMetadata || $search !== '') {
			$query->leftJoin('m', 'accounts_data', 'dn',
				$query->expr()->andX(
					$query->expr()->eq('dn.uid', 'm.uid'),
					$query->expr()->eq('dn.name', $query->createNamedParameter('displayname'))
				)
			);
			$query->leftJoin('m', 'accounts_data', 'em',
				$query->expr()->andX(
					$query->expr()->eq('em.uid', 'm.uid'),
					$query->expr()->eq('em.name', $query->createNamedParameter('email'))
				)
			);
		}

		if ($search !== '') {
			$searchParam1 = $query->createNamedParameter('%' . $this->dbc->escapeLikeParameter($search) . '%');
			$searchParam2 = $query->createNamedParameter('%' . $this->dbc->escapeLikeParameter($search) . '%');
			$searchParam3 = $query->createNamedParameter('%' . $this->dbc->escapeLikeParameter($search) . '%');
			$query->andWhere(
				$query->expr()->orX(
					$query->expr()->ilike('m.uid', $searchParam1),
					$query->expr()->ilike('dn.value', $searchParam2),
					$query->expr()->ilike('em.value', $searchParam3)
				)
			);
		}
	}

	#[\Override]
	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		$query = $this->dbc->getQueryBuilder();
		$query->select('g.uid', 'dn.value AS displayname')
			->from(self::TABLE_MEMBERS, 'g')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->orderBy('g.uid', 'ASC');

		$this->setupSearchQuery($search, $query, includeMetadata: true);

		if ($limit !== -1) {
			$query->setMaxResults($limit);
		}
		if ($offset !== 0) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();

		$users = [];
		$userManager = Server::get(IUserManager::class);
		while ($row = $result->fetch()) {
			/** @psalm-suppress RedundantConditionGivenDocblockType */
			if (method_exists($userManager, 'getExistingUser')) {
				$users[(string)$row['uid']] = $userManager->getExistingUser($row['uid'], $row['displayname'] ?? null);
			} else {
				/** @psalm-suppress UndefinedClass */
				$user = new LazyUser($row['uid'], $userManager, $row['displayname'] ?? null);
				/** @var IUser $user */
				$users[(string)$row['uid']] = $user;
			}
		}
		$result->closeCursor();

		return $users;
	}
}
