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
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\UserInterface;
use OCP\IUserBackend;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ISession;
use Symfony\Component\EventDispatcher\GenericEvent;

class UserBackend implements IApacheBackend, UserInterface, IUserBackend {
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ISession */
	private $session;
	/** @var IDBConnection */
	private $db;
	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var \OCP\UserInterface[] */
	private static $backends = [];
	/** @var SAMLSettings */
	private $settings;
	/** @var ILogger */
	private $logger;
	/** @var UserData */
	private $userData;

	public function __construct(
		IConfig $config,
		IURLGenerator $urlGenerator,
		ISession $session,
		IDBConnection $db,
		IUserManager $userManager,
		IGroupManager $groupManager,
		SAMLSettings $settings,
		ILogger $logger,
		UserData $userData
) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->db = $db;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->settings = $settings;
		$this->logger = $logger;
		$this->userData = $userData;
	}

	/**
	 * Whether $uid exists in the database
	 *
	 * @param string $uid
	 * @return bool
	 */
	protected function userExistsInDatabase($uid) {
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
	 * Creates a user if it does not exist. In case home directory mapping
	 * is enabled we also set up the user's home from $attributes.
	 *
	 * @param string $uid
	 * @param array $attributes
	 */
	public function createUserIfNotExists($uid, array $attributes = array()) {
		if(!$this->userExistsInDatabase($uid)) {
			$values = [
				'uid' => $uid,
			];

			// Try to get the mapped home directory of the user
			try {
				$home = $this->getAttributeValue('saml-attribute-mapping-home_mapping', $attributes);
			} catch (\InvalidArgumentException $e) {
				$home = '';
			}

			if ($home !== '') {
				//if attribute's value is an absolute path take this, otherwise append it to data dir
				//check for / at the beginning or pattern c:\ resp. c:/
				if(   '/' !== $home[0]
				   && !(3 < strlen($home) && ctype_alpha($home[0])
					   && $home[1] === ':' && ('\\' === $home[2] || '/' === $home[2]))
				) {
					$home = $this->config->getSystemValue('datadirectory',
							\OC::$SERVERROOT.'/data' ) . '/' . $home;
				}

				$values['home'] = $home;
			}

			/* @var $qb IQueryBuilder */
			$qb = $this->db->getQueryBuilder();
			$qb->insert('user_saml_users');
			foreach($values as $column => $value) {
				$qb->setValue($column, $qb->createNamedParameter($value));
			}
			$qb->execute();

			$this->initializeHomeDir($uid);

		}
	}

	/**
	 * @param string $uid
	 * @throws \OCP\Files\NotFoundException
	 */
	public function initializeHomeDir($uid) {
		### Code taken from lib/private/User/Session.php - function prepareUserLogin() ###
		//trigger creation of user home and /files folder
		$userFolder = \OC::$server->getUserFolder($uid);
		try {
			// copy skeleton
			\OC_Util::copySkeleton($uid, $userFolder);
		} catch (NotPermittedException $ex) {
			// read only uses
		}
		// trigger any other initialization
		$user = $this->userManager->get($uid);
		\OC::$server->getEventDispatcher()->dispatch(IUser::class . '::firstLogin', new GenericEvent($user));
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\User\Backend::CREATE_USER etc.
	 * @since 4.5.0
	 */
	public function implementsActions($actions) {
		$availableActions = \OC\User\Backend::CHECK_PASSWORD;
		$availableActions |= \OC\User\Backend::GET_DISPLAYNAME;
		$availableActions |= \OC\User\Backend::GET_HOME;
		$availableActions |= \OC\User\Backend::COUNT_USERS;
		return (bool)($availableActions & $actions);
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
	 * Returns the user's home directory, if home directory mapping is set up.
	 *
	 * @param string $uid the username
	 * @return string
	 */
	public function getHome($uid) {
		if($this->userExistsInDatabase($uid)) {
			$qb = $this->db->getQueryBuilder();
			$qb->select('home')
				->from('user_saml_users')
				->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
				->setMaxResults(1);
			$result = $qb->execute();
			$users = $result->fetchAll();
			if (isset($users[0]['home'])) {
				return $users[0]['home'];
			}
		}
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
		// shamelessly duplicated from \OC\User\Database
		$users = $this->getDisplayNames($search, $limit, $offset);
		$userIds = array_map(function ($uid) {
			return (string)$uid;
		}, array_keys($users));
		sort($userIds, SORT_STRING | SORT_FLAG_CASE);
		return $userIds;
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

	public function setDisplayName($uid, $displayName) {
		if($backend = $this->getActualUserBackend($uid)) {
			return $backend->setDisplayName($uid, $displayName);
		}

		if ($this->userExistsInDatabase($uid)) {
			$qb = $this->db->getQueryBuilder();
			$qb->update('user_saml_users')
				->set('displayname', $qb->createNamedParameter($displayName))
				->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
				->execute();
			return true;
		}

		return false;
	}

	/**
	 * Get display name of the user
	 *
	 * @param string $uid user ID of the user
	 * @return string display name
	 * @since 4.5.0
	 */
	public function getDisplayName($uid) {
		if($backend = $this->getActualUserBackend($uid)) {
			return $backend->getDisplayName($uid);
		} else {
			if($this->userExistsInDatabase($uid)) {
				$qb = $this->db->getQueryBuilder();
				$qb->select('displayname')
					->from('user_saml_users')
					->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
					->setMaxResults(1);
				$result = $qb->execute();
				$users = $result->fetchAll();
				if (isset($users[0]['displayname'])) {
					return $users[0]['displayname'];
				}
			}
		}

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
		// shamelessly duplicate from \OC\User\Database
		$query = $this->db->getQueryBuilder();

		$query->select('uid', 'displayname')
			->from('user_saml_users', 'u')
			->leftJoin('u', 'preferences', 'p', $query->expr()->andX(
				$query->expr()->eq('userid', 'uid'),
				$query->expr()->eq('appid', $query->expr()->literal('settings')),
				$query->expr()->eq('configkey', $query->expr()->literal('email')))
			)
			// sqlite doesn't like re-using a single named parameter here
			->where($query->expr()->iLike('uid', $query->createPositionalParameter('%' . $this->db->escapeLikeParameter($search) . '%')))
			->orWhere($query->expr()->iLike('displayname', $query->createPositionalParameter('%' . $this->db->escapeLikeParameter($search) . '%')))
			->orWhere($query->expr()->iLike('configvalue', $query->createPositionalParameter('%' . $this->db->escapeLikeParameter($search) . '%')))
			->orderBy($query->func()->lower('displayname'), 'ASC')
			->orderBy('uid', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		$result = $query->execute();
		$displayNames = [];
		while ($row = $result->fetch()) {
			$displayNames[(string)$row['uid']] = (string)$row['displayname'];
		}
		$result->closeCursor();

		return $displayNames;
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
		return $this->session->get('user_saml.samlUserData') !== null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogoutUrl() {
		$id = $this->settings->getProviderId();
		$settings = $this->settings->get($id);
		$slo = $settings['idp-singleLogoutService.url'] ?? '';

		if ($slo === '') {
			return '';
		}

		return $this->urlGenerator->linkToRouteAbsolute(
			'user_saml.SAML.singleLogoutService',
			[
				'requesttoken' => \OC::$server->getCsrfTokenManager()->getToken()->getEncryptedValue(),
			]
		);
	}

	/**
	 * Logout attribute for Nextcloud < 12.0.3
	 *
	 * @return string
	 */
	public function getLogoutAttribute() {
		return 'style="display:none;"';
	}

	/**
	 * return user data from the idp
	 *
	 * @return mixed
	 */
	public function getUserData() {
		$userData = $this->session->get('user_saml.samlUserData');
		$userData = $this->formatUserData($userData);

		// make sure that a valid UID is given
		if (empty($userData['formatted']['uid'])) {
			$this->logger->error('No valid uid given, please check your attribute mapping. Got uid: {uid}', ['app' => $this->appName, 'uid' => $userData['uid']]);
			throw new \InvalidArgumentException('No valid uid given, please check your attribute mapping. Got uid: ' . $userData['uid']);
		}

		return $userData;
	}

	/**
	 * format user data and map them to the configured attributes
	 *
	 * @param $attributes
	 * @return array
	 */
	private function formatUserData($attributes) {
		$this->userData->setAttributes($attributes);

		$result = ['formatted' => [], 'raw' => $attributes];

		try {
			$result['formatted']['email'] = $this->getAttributeValue('saml-attribute-mapping-email_mapping', $attributes);
		} catch (\InvalidArgumentException $e) {
			$result['formatted']['email'] = null;
		}
		try {
			$result['formatted']['displayName'] = $this->getAttributeValue('saml-attribute-mapping-displayName_mapping', $attributes);
		} catch (\InvalidArgumentException $e) {
			$result['formatted']['displayName'] = null;
		}
		try {
			$result['formatted']['quota'] = $this->getAttributeValue('saml-attribute-mapping-quota_mapping', $attributes);
			if ($result['formatted']['quota'] === '') {
				$result['formatted']['quota'] = 'default';
			}
		} catch (\InvalidArgumentException $e) {
			$result['formatted']['quota'] = null;
		}

		try {
			$result['formatted']['groups'] = $this->getAttributeArrayValue('saml-attribute-mapping-group_mapping', $attributes);
		} catch (\InvalidArgumentException $e) {
			$result['formatted']['groups'] = null;
		}

		$result['formatted']['uid'] = $this->userData->getEffectiveUid();

		return $result;
	}

	/**
	 * Return the id of the current user
	 * @return string
	 * @since 6.0.0
	 */
	public function getCurrentUserId() {
		$user = \OC::$server->getUserSession()->getUser();

		if($user instanceof IUser && $this->session->get('user_saml.samlUserData')) {
			$uid = $user->getUID();
		} else {
			$this->userData->setAttributes($this->session->get('user_saml.samlUserData') ?? []);
			$uid = $this->userData->getEffectiveUid();
		}

		if($uid !== '' && $this->userExists($uid)) {
			$this->session->set('last-password-confirm', strtotime('+4 year', time()));
			return $uid;
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
		foreach(self::$backends as $backend) {
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
		self::$backends = $backends;
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	private function getAttributeKeys($name)
	{
		$settings = $this->settings->get($this->settings->getProviderId());
		$keys = explode(' ', $settings[$name] ?? $this->config->getAppValue('user_saml', $name, ''));

		if (count($keys) === 1 && $keys[0] === '') {
			throw new \InvalidArgumentException('Attribute is not configured');
		}
		return $keys;
	}

	private function getAttributeValue($name, array $attributes) {
		$keys = $this->getAttributeKeys($name);

		$value = '';
		foreach($keys as $key) {
			if (isset($attributes[$key])) {
				if (is_array($attributes[$key])) {
					foreach ($attributes[$key] as $attribute_part_value) {
						if($value !== '') {
							$value .= ' ';
						}
						$value .= $attribute_part_value;
					}
				} else {
					if($value !== '') {
						$value .= ' ';
					}
					$value .= $attributes[$key];
				}
			}
		}

		return $value;
	}

	private function getAttributeArrayValue($name, array $attributes) {
		$keys = $this->getAttributeKeys($name);

		$value = array();
		foreach($keys as $key) {
			if (isset($attributes[$key])) {
				if (is_array($attributes[$key])) {
					$value = array_merge($value, array_values($attributes[$key]));
				} else {
					$value[] = $attributes[$key];
				}
			}
		}

		return $value;
	}

	public function updateAttributes($uid,
									 array $attributes) {
		$user = $this->userManager->get($uid);
		try {
			$newEmail = $this->getAttributeValue('saml-attribute-mapping-email_mapping', $attributes);
			$this->logger->debug('Email attribute content: {email}', ['app' => 'user_saml', 'email' => $newEmail]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->debug('Failed to fetch email attribute: {exception}', ['app' => 'user_saml', 'exception' => $e->getMessage()]);
			$newEmail = null;
		}
		try {
			$newDisplayname = $this->getAttributeValue('saml-attribute-mapping-displayName_mapping', $attributes);
			$this->logger->debug('Display name attribute content: {displayName}', ['app' => 'user_saml', 'displayName' => $newDisplayname]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->debug('Failed to fetch display name attribute: {exception}', ['app' => 'user_saml', 'exception' => $e->getMessage()]);
			$newDisplayname = null;
		}
		try {
			$newQuota = $this->getAttributeValue('saml-attribute-mapping-quota_mapping', $attributes);
			$this->logger->debug('Quota attribute content: {quota}', ['app' => 'user_saml', 'quota' => $newQuota]);
			if ($newQuota === '') {
				$newQuota = 'default';
			}
		} catch (\InvalidArgumentException $e) {
			$this->logger->debug('Failed to fetch quota attribute: {exception}', ['app' => 'user_saml', 'exception' => $e->getMessage()]);
			$newQuota = null;
		}

		try {
			$newGroups = $this->getAttributeArrayValue('saml-attribute-mapping-group_mapping', $attributes);
			$this->logger->debug('Group attribute content: {groups}', ['app' => 'user_saml', 'groups' => json_encode($newGroups)]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->debug('Failed to fetch group attribute: {exception}', ['app' => 'user_saml', 'exception' => $e->getMessage()]);
			$newGroups = null;
		}

		if ($user !== null) {
			$currentEmail = (string)(method_exists($user, 'getSystemEMailAddress') ? $user->getSystemEMailAddress() : $user->getEMailAddress());
			if ($newEmail !== null
				&& $currentEmail !== $newEmail) {
				$user->setEMailAddress($newEmail);
			}
			$currentDisplayname = (string)$this->getDisplayName($uid);
			if ($newDisplayname !== null
				&& $currentDisplayname !== $newDisplayname) {
				\OC_Hook::emit('OC_User', 'changeUser',
					[
						'user' => $user,
						'feature' => 'displayName',
						'value' => $newDisplayname
					]
				);
				$this->setDisplayName($uid, $newDisplayname);
			}

			if ($newQuota !== null) {
				$user->setQuota($newQuota);
			}

			if ($newGroups !== null) {
				$groupManager = $this->groupManager;
				$oldGroups = $groupManager->getUserGroupIds($user);

				$groupsToAdd = array_unique(array_diff($newGroups, $oldGroups));
				$groupsToRemove = array_diff($oldGroups, $newGroups);

				foreach ($groupsToAdd as $group) {
					if (!($groupManager->groupExists($group))) {
						$groupManager->createGroup($group);
					}
					$groupManager->get($group)->addUser($user);
				}

				foreach ($groupsToRemove as $group) {
					$groupManager->get($group)->removeUser($user);
				}
			}
		}
	}



	public function countUsers() {
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('uid'))
			->from('user_saml_users');
		$result = $query->execute();

		return $result->fetchColumn();
	}
}
