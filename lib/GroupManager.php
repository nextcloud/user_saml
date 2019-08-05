<?php

namespace OCA\User_SAML;

use OCP\IDBConnection;

class GroupManager
{
	/**
	 * @var IDBConnection $db
	 */
	protected $db;

	/**
	 * @var GroupDuplicateChecker
	 */
	protected $duplicateChecker;

	public function __construct(IDBConnection $db, GroupDuplicateChecker $duplicateChecker) {
		$this->db = $db;
		$this->duplicateChecker = $duplicateChecker;
	}

	/**
	 * @return string[]
	 */
	public function findGroups($query = null, $limit = -1, $offset = 0) {
		$sql = '
			SELECT DISTINCT `group`
			FROM `*PREFIX*user_saml_group_members`
		';

		$params = array();

		if ($query !== null) {
			$sql .= ' WHERE `group` LIKE ?';
			$params = [
				'%' . $query . '%'
			];
		}

		if ($limit === -1) {
			$limit = null;
		}

		if ($offset === 0) {
			$offset = null;
		}

		$stmt = $this->db->prepare($sql, $limit, $offset);
		$stmt->execute($params);
		return $stmt->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * @return bool
	 */
	public function hasGroup($group) {
		$sql = '
			SELECT DISTINCT `group`
			FROM `*PREFIX*user_saml_group_members`
			WHERE `group` = ?
		';

		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($group));
		return $stmt->rowCount() > 0;
	}

	/**
	 * @return bool
	 */
	public function userInGroup($uid, $group) {
		$sql = '
			SELECT DISTINCT `group`
			FROM `*PREFIX*user_saml_group_members`
			WHERE `uid` = ? AND `group` = ?
		';

		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($uid, $group));
		return $stmt->rowCount() > 0;
	}

	/**
	 * @return string[]
	 */
	public function userGroups($uid) {
		$sql = '
			SELECT DISTINCT `group`
			FROM `*PREFIX*user_saml_group_members`
			WHERE `uid` = ?
		';

		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($uid));
		return $stmt->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * @return string[]
	 */
	public function usersInGroup($gid, $query = null, $limit = -1, $offset = 0) {
		$sql = '
			SELECT DISTINCT `uid`
			FROM `*PREFIX*user_saml_group_members`
			WHERE `group` = ?
		';

		$params = array($gid);

		if ($query !== null) {
			$sql .= ' AND `uid` LIKE ?';
			$params = [
				'%' . $query . '%'
			];
		}

		if ($limit === -1) {
			$limit = null;
		}

		if ($offset === 0) {
			$offset = null;
		}

		$stmt = $this->db->prepare($sql, $limit, $offset);
		$stmt->execute($params);
		return $stmt->fetchAll(\PDO::FETCH_COLUMN);
	}

	public function replaceGroups($uid, $saml) {
		$assgined = $this->userGroups($uid);
		$this->removeGroups($uid, array_diff($assgined, $saml));
		$this->addGroups($uid, array_diff($saml, $assgined));
	}

	public function removeGroup($uid, $group) {
		$this->removeGroups($uid, array($group));
	}

	public function removeGroups($uid, $groups) {
		if (count($groups) === 0) {
			return;
		}

		$groups = array_values($groups);
		$inQuery = implode(',', array_fill(0, count($groups), '?'));

		$sql = '
			DELETE FROM `*PREFIX*user_saml_group_members`
			WHERE `uid` = ? AND `group` IN (' . $inQuery . ')
		';

		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $uid);
		foreach ($groups as $k => $id) {
			$stmt->bindValue(($k + 2), $id);
		}
		return $stmt->execute();
	}

	public function addGroups($uid, $groups) {
		foreach ($groups as $group) {
			$this->addGroup($uid, $group);
		}
	}

	public function addGroup($uid, $group) {
		$this->duplicateChecker->checkForDuplicates($group);

		$sql = '
			INSERT INTO `*PREFIX*user_saml_group_members`
		    (`uid`, `group`)
		    VALUES (?, ?)
		';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute(array($uid, $group));
	}
}
