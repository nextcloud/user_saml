<?php
/**
 * @copyright Copyright (c) 2019 Dominik Ach <da@infodatacom.de>
 *
 * @author Dominik Ach <da@infodatacom.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Maximilian Ruta <mr@xtain.net>
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Giuliano Mele <giuliano.mele@verdigado.com>
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

namespace OCA\User_SAML;

use OCP\DB\Exception;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\IDBConnection;

class GroupBackend extends ABackend implements IAddToGroupBackend, ICountUsersBackend, ICreateGroupBackend, IDeleteGroupBackend, IRemoveFromGroupBackend, INamedBackend {
	/** @var IDBConnection */
	private $dbc;

	/** @var array  */
	private $groupCache = [];

	public const TABLE_GROUPS = 'user_saml_groups';
	public const TABLE_MEMBERS = 'user_saml_group_members';

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
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
	 * @return string[] Group names
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
			$this->groupCache[$row['gid']] = $row['gid'];
		}
		$cursor->closeCursor();

		return $groups;
	}

	/**
	 * @return string[] Group names
	 */
	public function getGroups($search = '', $limit = null, $offset = null): array {
		$query = $this->dbc->getQueryBuilder();
		$query->select('gid')
			->from(self::TABLE_GROUPS)
			->orderBy('gid', 'ASC');

		if ($search !== '') {
			$query->where($query->expr()->iLike('gid', $query->createNamedParameter(
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
		$cursor = $qb->select('gid')
			->from(self::TABLE_GROUPS)
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeQuery();
		$result = $cursor->fetch();
		$cursor->closeCursor();

		if ($result !== false) {
			$this->groupCache[$gid] = $gid;
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
		$result = $cursor->fetch(\PDO::FETCH_NUM);
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
	 * @return string[] User ids
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

	public function createGroup(string $gid, string $samlGid = null): bool {
		try {
			// Add group
			$builder = $this->dbc->getQueryBuilder();
			$samlGid = $samlGid ?? $gid;
			$result = $builder->insert(self::TABLE_GROUPS)
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($samlGid))
				->setValue('saml_gid', $builder->createNamedParameter($samlGid))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$result = 0;
			}
		}

		// Add to cache
		$this->groupCache[$gid] = $gid;

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
}
