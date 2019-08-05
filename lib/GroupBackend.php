<?php

namespace OCA\User_SAML;

use OCP\Group\Backend\ABackend;
use OCP\IConfig;

class GroupBackend extends ABackend {
	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @var GroupManager
	 */
	protected $groupManager;

	public function __construct(
		IConfig $config,
		GroupManager $groupManager
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * @return string
	 */
	protected function getPrefix() {
		return $this->config->getAppValue('user_saml', 'saml-attribute-mapping-group_mapping_prefix', '');
	}

	protected function hasPrefix($string) {
		return mb_substr($string, 0, mb_strlen($this->getPrefix())) === $this->getPrefix();
	}

	protected function removePrefix($query = null) {
		if ($query === null || $query === '') {
			return null;
		}

		$pattern = '';
		foreach (preg_split('//u', $this->getPrefix(), -1, PREG_SPLIT_NO_EMPTY) as $char) {
			$pattern .= preg_quote($char, '/') . '?';
		}

		$result = preg_replace('/^' . $pattern . '/', '', $query);

		if ($result === '') {
			return null;
		}

		return $result;
	}

	protected function prefixedList($items) {
		$newList = array();

		foreach ($items as $item) {
			$newList[] = $this->getPrefix() . $item;
		}

		return $newList;
	}

	/**
	 * @return bool
	 */
	public function inGroup($uid, $gid) {
		if (!$this->hasPrefix($gid)) {
			return false;
		}

		return $this->groupManager->userInGroup($uid, $this->removePrefix($gid));
	}

	/**
	 * @return string[] Group names
	 */
	public function getUserGroups($uid) {
		return $this->prefixedList($this->groupManager->userGroups($uid));
	}

	/**
	 * @return string[] Group names
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		if ($search === '') {
			$search = null;
		} else {
			if (!$this->hasPrefix($search)) {
				return [];
			}
		}

		return $this->prefixedList($this->groupManager->findGroups($this->removePrefix($search), $limit, $offset));
	}

	/**
	 * @return bool
	 */
	public function groupExists($gid) {
		if (!$this->hasPrefix($gid)) {
			return false;
		}

		return $this->groupManager->hasGroup(
			$this->removePrefix($gid)
		);
	}

	/**
	 * @return string[] User ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if ($search === '') {
			$search = null;
		} else {
			if (!$this->hasPrefix($search)) {
				return [];
			}
		}

		return $this->prefixedList(
			$this->groupManager->findGroups($gid, $this->removePrefix($search), $limit, $offset)
		);
	}
}
