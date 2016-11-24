<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

use OCP\Authentication\IApacheBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDb;
use OCP\UserInterface;
use OCP\IUserBackend;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ISession;

class UserBackend implements IApacheBackend, UserInterface, IUserBackend {
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ISession */
	private $session;
	/** @var IDb */
	private $db;
	/** @var \OCP\UserInterface[] */
	private $backends;

	/**
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param ISession $session
	 * @param IDb $db
	 */
	public function __construct(IConfig $config,
								IURLGenerator $urlGenerator,
								ISession $session,
								IDb $db) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->db = $db;
	}

	/**
	 * Whether $uid exists in the database
	 *
	 * @param string $uid
	 * @return bool
	 */
	private function userExistsInDatabase($uid) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('uid')
			->from('user_saml_users')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->setMaxResults(1);
		$result = $qb->execute();
		$users = $result->fetchAll();
		$result->closeCursor();

		return !empty($users);
	}

	/**
	 * Creates an user if it does not exists
	 *
	 * @param string $uid
	 */
	public function createUserIfNotExists($uid) {
		if(!$this->userExistsInDatabase($uid)) {
			$values = [
				'uid' => $uid,
			];

			/* @var $qb IQueryBuilder */
			$qb = $this->db->getQueryBuilder();
			$qb->insert('user_saml_users');
			foreach($values as $column => $value) {
				$qb->setValue($column, $qb->createNamedParameter($value));
			}
			$qb->execute();
		}
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC_User_Backend::CREATE_USER etc.
	 * @since 4.5.0
	 */
	public function implementsActions($actions) {
		return (bool)((\OC_User_Backend::CHECK_PASSWORD)
			& $actions);
	}

	/**
	 * Check if the provided token is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('token')
			->from('user_saml_auth_token')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->setMaxResults(1000);
		$result = $qb->execute();
		$data = $result->fetchAll();
		$result->closeCursor();

		foreach($data as $passwords) {
			if(password_verify($password, $passwords['token'])) {
				return $uid;
			}
		}

		return false;
	}

	/**
	 * delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 * @since 4.5.0
	 */
	public function deleteUser($uid) {
		if($this->userExistsInDatabase($uid)) {
			/* @var $qb IQueryBuilder */
			$qb = $this->db->getQueryBuilder();
			$qb->delete('user_saml_users')
				->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
				->execute();
			return true;
		}
		return false;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param null|int $limit
	 * @param null|int $offset
	 * @return string[] an array of all uids
	 * @since 4.5.0
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('uid', 'displayname')
			->from('user_saml_users')
			->where(
				$qb->expr()->iLike('uid', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($search) . '%'))
			)
			->setMaxResults($limit);
		if($offset !== null) {
			$qb->setFirstResult($offset);
		}
		$result = $qb->execute();
		$users = $result->fetchAll();
		$result->closeCursor();

		$uids = [];
		foreach($users as $user) {
			$uids[] = $user['uid'];
		}

		return $uids;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 * @since 4.5.0
	 */
	public function userExists($uid) {
		if($backend = $this->getActualUserBackend($uid)) {
			return $backend->userExists($uid);
		} else {
			return $this->userExistsInDatabase($uid);
		}
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 * @since 4.5.0
	 */
	public function getDisplayName($uid) {
		return false;
	}

	/**
	 * Get a list of all display names and user ids.
	 *
	 * @param string $search
	 * @param string|null $limit
	 * @param string|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 * @since 4.5.0
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('uid', 'displayname')
			->from('user_saml_users')
			->where(
				$qb->expr()->iLike('uid', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($search) . '%'))
			)
			->setMaxResults($limit);
		if($offset !== null) {
			$qb->setFirstResult($offset);
		}
		$result = $qb->execute();
		$users = $result->fetchAll();
		$result->closeCursor();

		$uids = [];
		foreach($users as $user) {
			$uids[$user['uid']] = $user['displayname'];
		}

		return $uids;
	}

	/**
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 * @since 4.5.0
	 */
	public function hasUserListings() {
		if($this->autoprovisionAllowed()) {
			return true;
		}

		return false;
	}

	/**
	 * In case the user has been authenticated by Apache true is returned.
	 *
	 * @return boolean whether Apache reports a user as currently logged in.
	 * @since 6.0.0
	 */
	public function isSessionActive() {
		if($this->getCurrentUserId() !== '') {
			return true;
		}
		return false;
	}

	/**
	 * Creates an attribute which is added to the logout hyperlink. It can
	 * supply any attribute(s) which are valid for <a>.
	 *
	 * @return string with one or more HTML attributes.
	 * @since 6.0.0
	 */
	public function getLogoutAttribute() {
		$slo = $this->config->getAppValue('user_saml', 'idp-singleLogoutService.url', '');
		if($slo === '') {
			return 'style="display:none;"';
		}

		return 'href="'.$this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.singleLogoutService').'?requesttoken='.urlencode(\OC::$server->getCsrfTokenManager()->getToken()->getEncryptedValue()).'"';
	}

	/**
	 * Return the id of the current user
	 * @return string
	 * @since 6.0.0
	 */
	public function getCurrentUserId() {
		$samlData = $this->session->get('user_saml.samlUserData');
		$uidMapping = $this->config->getAppValue('user_saml', 'general-uid_mapping', '');

		if($uidMapping !== '' && isset($samlData[$uidMapping])) {
			if(is_array($samlData[$uidMapping])) {
				$uid = $samlData[$uidMapping][0];
			} else {
				$uid = $samlData[$uidMapping];
			}
			if($this->userExists($uid)) {
				$this->session->set('last-password-confirm', time());
				return $uid;
			}
		}

		return '';
	}


	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 * @since 8.0.0
	 */
	public function getBackendName() {
		return 'user_saml';
	}

	/**
	 * Whether autoprovisioning is enabled or not
	 *
	 * @return bool
	 */
	public function autoprovisionAllowed() {
		return $this->config->getAppValue('user_saml', 'general-require_provisioned_account', '0') === '0';
	}

	/**
	 * Gets the actual user backend of the user
	 *
	 * @param string $uid
	 * @return null|UserInterface
	 */
	public function getActualUserBackend($uid) {
		foreach($this->backends as $backend) {
			if($backend->userExists($uid)) {
				return $backend;
			}
		}

		return null;
	}

	/**
	 * Registers the used backends, used later to get the actual user backend
	 * of the user.
	 *
	 * @param \OCP\UserInterface[] $backends
	 */
	public function registerBackends(array $backends) {
		$this->backends = $backends;
	}

}
