<?php

namespace OCA\User_SAML;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\FetchMode;
use OCA\User_SAML\Exceptions\AddUserToGroupException;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\IDBConnection;

class GroupBackend extends ABackend implements IAddToGroupBackend, IRemoveFromGroupBackend, ICountUsersBackend, IDeleteGroupBackend {
	/** @var IDBConnection */
	private $dbc;

	/** @var array  */
	private $groupCache = [];

	const TABLE_GROUPS = 'user_saml_groups';
	const TABLE_MEMBERS = 'user_saml_group_members';

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
	}

	public function inGroup($uid, $gid) {
		$qb = $this->dbc->getQueryBuilder();
		$stmt = $qb->select('gid')
			->from(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->setMaxResults(1)
			->execute();

		$result = count($stmt->fetchAll()) > 0;
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @return string[] Group names
	 */
	public function getUserGroups($uid) {
		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid')
			->from(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->execute();

		$groups = [];
		while( $row = $cursor->fetch()) {
			$groups[] = $row['gid'];
			$this->groupCache[$row['gid']] = $row['gid'];
		}
		$cursor->closeCursor();

		return $groups;
	}

	/**
	 * @return string[] Group names
	 */
	public function getGroups($search = '', $limit = null, $offset = null) {
		$query = $this->dbc->getQueryBuilder();
		$query->select('gid')
			->from(self::TABLE_GROUPS)
			->orderBy('gid', 'ASC');

		if ($search !== '') {
			$query->where($query->expr()->iLike('gid', $query->createNamedParameter(
				'%' . $this->dbc->escapeLikeParameter($search) . '%'
			)));
		}

		$query->setMaxResults($limit)
			->setFirstResult($offset);
		$result = $query->execute();

		$groups = [];
		while ($row = $result->fetch()) {
			$groups[] = $row['gid'];
		}
		$result->closeCursor();

		return $groups;
	}

	/**
	 * @return bool
	 */
	public function groupExists($gid) {
		if (isset($this->groupCache[$gid])) {
			return true;
		}

		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid')
			->from(self::TABLE_GROUPS)
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->execute();
		$result = $cursor->fetch();
		$cursor->closeCursor();

		if ($result !== false) {
			$this->groupCache[$gid] = $gid;
			return true;
		}
		return false;
	}

	public function groupExistsWithDifferentGid($samlGid): ?string {
		$qb = $this->dbc->getQueryBuilder();
		$cursor = $qb->select('gid')
			->from(self::TABLE_GROUPS)
			->where($qb->expr()->eq('saml_gid', $qb->createNamedParameter($samlGid)))
			->execute();
		$result = $cursor->fetch(FetchMode::NUMERIC);
		$cursor->closeCursor();

		if ($result !== false) {
			return $result[0];
		}
		return null;
	}

	/**
	 * @return string[] User ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
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

		$result = $query->execute();

		$users = [];
		while ($row = $result->fetch()) {
			$users[] = $row['uid'];
		}
		$result->closeCursor();

		return $users;
	}

	public function createGroup(string $gid, string $samlGid = null): bool {
		try {
			// Add group
			$builder = $this->dbc->getQueryBuilder();
			$displayName = $samlGid ? $gid . ' (SAML)' : $gid;
			$samlGid = $samlGid ?? $gid;
			$result = $builder->insert(self::TABLE_GROUPS)
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($displayName))
				->setValue('saml_gid', $builder->createNamedParameter($samlGid))
				->execute();
		} catch(UniqueConstraintViolationException $e) {
			$result = 0;
		}

		// Add to cache
		$this->groupCache[$gid] = $gid;

		return $result === 1;
	}

	public function addToGroup(string $uid, string $gid): bool {
		try {
			$qb = $this->dbc->getQueryBuilder();
			$qb->insert(self::TABLE_MEMBERS)
				->setValue('uid', $qb->createNamedParameter($uid))
				->setValue('gid', $qb->createNamedParameter($gid))
				->execute();
			return true;
		} catch (\Exception $e) {
			throw new AddUserToGroupException($e->getMessage());
		}
	}

	public function removeFromGroup(string $uid, string $gid): bool {
		$qb = $this->dbc->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBERS)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		return true;
	}

	public function countUsersInGroup(string $gid, string $search = ''): int {
		$query = $this->dbc->getQueryBuilder();
		$query->select($query->func()->count('*', 'num_users'))
			->from(self::TABLE_MEMBERS)
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));

		if ($search !== '') {
			$query->andWhere($query->expr()->like('uid', $query->createNamedParameter(
				'%' . $this->dbConn->escapeLikeParameter($search) . '%'
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
		// delete the group
		$query->delete(self::TABLE_GROUPS)
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->executeStatement();

		// delete group user relation
		$query->delete(self::TABLE_MEMBERS)
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->executeStatement();

		// remove from cache
		unset($this->groupCache[$gid]);
		return true;
	}
}
