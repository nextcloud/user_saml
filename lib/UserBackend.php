<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OC\Security\CSRF\CsrfTokenManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Authentication\IApacheBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\ICustomLogout;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\User\Backend\IGetHomeBackend;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserFirstTimeLoggedInEvent;
use OCP\UserInterface;
use Override;
use Psr\Log\LoggerInterface;

class UserBackend extends ABackend implements IApacheBackend, IUserBackend, IGetDisplayNameBackend, ICountUsersBackend, IGetHomeBackend, ICustomLogout, ISetDisplayNameBackend {
	/** @var \OCP\UserInterface[] */
	private static array $backends = [];

	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IURLGenerator $urlGenerator,
		private readonly ISession $session,
		private readonly IDBConnection $db,
		private readonly IUserManager $userManager,
		private readonly GroupManager $groupManager,
		private readonly SAMLSettings $settings,
		private readonly LoggerInterface $logger,
		private readonly UserData $userData,
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * Whether $uid exists in the database
	 */
	protected function userExistsInDatabase(string $uid): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('uid')
			->from('user_saml_users')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$users = $result->fetchAll();
		$result->closeCursor();

		return !empty($users);
	}

	/**
	 * Creates a user if it does not exist. In case home directory mapping
	 * is enabled we also set up the user's home from $attributes.
	 */
	public function createUserIfNotExists(string $uid, array $attributes = []): void {
		if (!$this->userExistsInDatabase($uid)) {
			$values = [
				'uid' => $uid,
			];

			// Try to get the mapped home directory of the user
			try {
				$home = $this->getAttributeValue('saml-attribute-mapping-home_mapping', $attributes);
			} catch (\InvalidArgumentException) {
				$home = '';
			}

			if ($home !== '') {
				//if attribute's value is an absolute path take this, otherwise append it to data dir
				//check for / at the beginning or pattern c:\ resp. c:/
				if ($home[0] !== '/'
				   && !(strlen($home) > 3 && ctype_alpha($home[0])
					   && $home[1] === ':' && ($home[2] === '\\' || $home[2] === '/'))
				) {
					$home = $this->config->getSystemValueString('datadirectory',
						\OC::$SERVERROOT . '/data') . '/' . $home;
				}

				$values['home'] = $home;
			}

			$qb = $this->db->getQueryBuilder();
			$qb->insert('user_saml_users');
			foreach ($values as $column => $value) {
				$qb->setValue($column, $qb->createNamedParameter($value));
			}
			$qb->executeStatement();

			$this->initializeHomeDir($uid);
		}
	}

	/**
	 * @throws \OCP\Files\NotFoundException
	 */
	public function initializeHomeDir(string $uid): void {
		### Code taken from lib/private/User/Session.php - function prepareUserLogin() ###
		//trigger creation of user home and /files folder
		$userFolder = Server::get(IRootFolder::class)
			->getUserFolder($uid);
		try {
			// copy skeleton
			\OC_Util::copySkeleton($uid, $userFolder);
		} catch (NotPermittedException) {
			// read only uses
		}
		// trigger any other initialization
		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new \LogicException('Trying to initialize home dir for a non-existent user');
		}
		$this->eventDispatcher->dispatchTyped(new UserFirstTimeLoggedInEvent($user));
	}

	#[Override]
	public function deleteUser($uid): bool {
		$qb = $this->db->getQueryBuilder();
		$affected = $qb->delete('user_saml_users')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeStatement();
		return $affected > 0;
	}

	#[Override]
	public function getHome(string $uid): string|false {
		$qb = $this->db->getQueryBuilder();
		$qb->select('home')
			->from('user_saml_users')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$users = $result->fetchAll();
		$result->closeCursor();
		return $users[0]['home'] ?? false;
	}

	#[Override]
	public function getUsers($search = '', $limit = null, $offset = null): array {
		// shamelessly duplicated from \OC\User\Database
		$users = $this->getDisplayNames($search, $limit, $offset);
		$userIds = array_map(fn ($uid) => (string)$uid, array_keys($users));
		sort($userIds, SORT_STRING | SORT_FLAG_CASE);
		return $userIds;
	}

	#[Override]
	public function userExists($uid): bool {
		if ($backend = $this->getActualUserBackend($uid)) {
			return $backend->userExists($uid);
		} else {
			return $this->userExistsInDatabase($uid);
		}
	}

	#[Override]
	public function setDisplayName($uid, $displayName): bool {
		if ($backend = $this->getActualUserBackend($uid)) {
			/** @var ISetDisplayNameBackend $backend */
			return $backend->setDisplayName($uid, $displayName);
		}

		$qb = $this->db->getQueryBuilder();
		$affected = $qb->update('user_saml_users')
			->set('displayname', $qb->createNamedParameter($displayName))
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeStatement();
		return $affected > 0;
	}

	#[Override]
	public function getDisplayName($uid): string {
		if ($backend = $this->getActualUserBackend($uid)) {
			return $backend->getDisplayName($uid);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('displayname')
			->from('user_saml_users')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$users = $result->fetchAll();
		$result->closeCursor();
		return $users[0]['displayname'] ?? $uid;
	}

	#[Override]
	public function getDisplayNames($search = '', $limit = null, $offset = null): array {
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
			->addOrderBy('uid', 'ASC')
			->setMaxResults($limit);

		if ($offset !== null) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();
		$displayNames = [];
		while ($row = $result->fetch()) {
			$displayNames[(string)$row['uid']] = (string)$row['displayname'];
		}
		$result->closeCursor();

		return $displayNames;
	}

	#[Override]
	public function hasUserListings(): bool {
		return $this->autoprovisionAllowed();
	}

	#[Override]
	public function isSessionActive(): bool {
		return $this->session->get('user_saml.samlUserData') !== null;
	}

	#[Override]
	public function getLogoutUrl(): string {
		$id = $this->settings->getProviderId();
		$settings = $this->settings->get($id);
		$slo = $settings['idp-singleLogoutService.url'] ?? '';

		if ($slo === '') {
			return '';
		}

		$tokenManager = Server::get(CsrfTokenManager::class);
		return $this->urlGenerator->linkToRouteAbsolute(
			'user_saml.SAML.singleLogoutService',
			[
				'requesttoken' => $tokenManager->getToken()->getEncryptedValue(),
			]
		);
	}

	/**
	 * Return user data from the idp
	 *
	 * @return array{
	 *      formatted: array{
	 *          email: ?string,
	 *           displayName: ?string,
	 *          quota: ?string,
	 *          groups: ?list<string>,
	 *           mfaVerified: ?string,
	 *          uid: non-empty-string,
	 *      },
	 *      raw: array,
	 *  }
	 */
	public function getUserData(): array {
		$userData = $this->session->get('user_saml.samlUserData');
		$userData = $this->formatUserData($userData);

		// make sure that a valid UID is given
		if (empty($userData['formatted']['uid'])) {
			$this->logger->error('No valid uid given, please check your attribute mapping. Got uid: {uid}', ['app' => 'user_saml', 'uid' => $userData['formatted']['uid']]);
			throw new \InvalidArgumentException('No valid uid given, please check your attribute mapping. Got uid: ' . $userData['formatted']['uid']);
		}

		return $userData;
	}

	/**
	 * Format user data and map them to the configured attributes
	 *
	 * @return array{
	 *     formatted: array{
	 *         email: ?string,
	 *     	   displayName: ?string,
	 *         quota: ?string,
	 *         groups: ?list<string>,
	 *     	   mfaVerified: ?string,
	 *         uid: string,
	 *     },
	 *     raw: array,
	 * }
	 */
	private function formatUserData(array $attributes): array {
		$this->userData->setAttributes($attributes);

		$result = ['formatted' => [], 'raw' => $attributes];

		try {
			$result['formatted']['email'] = $this->getAttributeValue('saml-attribute-mapping-email_mapping', $attributes);
		} catch (\InvalidArgumentException) {
			$result['formatted']['email'] = null;
		}
		try {
			$result['formatted']['displayName'] = $this->getAttributeValue('saml-attribute-mapping-displayName_mapping', $attributes);
		} catch (\InvalidArgumentException) {
			$result['formatted']['displayName'] = null;
		}
		try {
			$result['formatted']['quota'] = $this->getAttributeValue('saml-attribute-mapping-quota_mapping', $attributes);
			if ($result['formatted']['quota'] === '') {
				$result['formatted']['quota'] = 'default';
			}
		} catch (\InvalidArgumentException) {
			$result['formatted']['quota'] = null;
		}

		try {
			$result['formatted']['groups'] = $this->userData->getGroups();
		} catch (\InvalidArgumentException) {
			$result['formatted']['groups'] = null;
		}

		try {
			$result['formatted']['mfaVerified'] = $this->getAttributeValue('saml-attribute-mapping-mfa_mapping', $attributes);
		} catch (\InvalidArgumentException) {
			$result['formatted']['mfaVerified'] = null;
		}

		$result['formatted']['uid'] = $this->userData->getEffectiveUid();

		return $result;
	}

	#[Override]
	public function getCurrentUserId(): string {
		$user = Server::get(IUserSession::class)->getUser();

		if ($user instanceof IUser && $this->session->get('user_saml.samlUserData')) {
			$uid = $user->getUID();
		} else {
			$this->userData->setAttributes($this->session->get('user_saml.samlUserData') ?? []);
			$uid = $this->userData->getEffectiveUid();
		}

		if ($uid !== '' && $this->userExists($uid)) {
			$this->session->set('last-password-confirm', strtotime('+4 year', time()));
			return $uid;
		}
		return '';
	}


	#[Override]
	public function getBackendName(): string {
		return 'user_saml';
	}

	/**
	 * Whether autoprovisioning is enabled or not
	 */
	public function autoprovisionAllowed(): bool {
		return $this->appConfig->getAppValueInt('general-require_provisioned_account') === 0;
	}

	/**
	 * Gets the actual user backend of the user
	 *
	 * @param string $uid
	 * @return null|UserInterface
	 */
	public function getActualUserBackend($uid) {
		foreach (self::$backends as $backend) {
			if ($backend->userExists($uid)) {
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
	public function registerBackends(array $backends): void {
		self::$backends = $backends;
	}

	/**
	 * @throws \OCP\DB\Exception
	 * @return string[]
	 */
	private function getAttributeKeys(string $name): array {
		$settings = $this->settings->get($this->settings->getProviderId());
		$keys = explode(' ', $settings[$name] ?? $this->appConfig->getAppValueString($name, ''));

		if (count($keys) === 1 && $keys[0] === '') {
			throw new \InvalidArgumentException('Attribute is not configured');
		}
		return $keys;
	}

	private function getAttributeValue(string $name, array $attributes): string {
		$keys = $this->getAttributeKeys($name);

		$value = '';
		foreach ($keys as $key) {
			if (isset($attributes[$key])) {
				if (is_array($attributes[$key])) {
					foreach ($attributes[$key] as $attribute_part_value) {
						if ($value !== '') {
							$value .= ' ';
						}
						$value .= $attribute_part_value;
					}
				} else {
					if ($value !== '') {
						$value .= ' ';
					}
					$value .= $attributes[$key];
				}
			}
		}

		return $value;
	}

	public function updateAttributes(string $uid): void {
		$attributes = $this->userData->getAttributes();
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
			$newGroups = $this->userData->getGroups();
			$this->logger->debug('Group attribute content: {groups}', ['app' => 'user_saml', 'groups' => json_encode($newGroups)]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->debug('Failed to fetch group attribute: {exception}', ['app' => 'user_saml', 'exception' => $e->getMessage()]);
			$newGroups = null;
		}

		if ($user !== null) {
			$this->logger->debug('Updating attributes for existing user', ['app' => 'user_saml', 'user' => $user->getUID()]);
			$currentEmail = (string)$user->getSystemEMailAddress();
			if ($newEmail !== null
				&& $currentEmail !== $newEmail) {
				$user->setSystemEMailAddress($newEmail);
				$this->logger->debug('Email address updated', ['app' => 'user_saml', 'user' => $user->getUID()]);
			} else {
				$this->logger->debug('Email address not updated', [
					'app' => 'user_saml',
					'user' => $user->getUID(),
					'currentEmail' => $currentEmail,
					'newEmail' => $newEmail,
				]);
			}
			$currentDisplayname = $this->getDisplayName($uid);
			if ($newDisplayname !== null
				&& $currentDisplayname !== $newDisplayname) {
				$this->setDisplayName($uid, $newDisplayname);
				$this->logger->debug('Display name updated', ['app' => 'user_saml', 'user' => $user->getUID()]);
				$this->eventDispatcher->dispatchTyped(new UserChangedEvent($user, 'displayName', $newDisplayname, $currentDisplayname));
				$this->logger->debug('Display name update event dispatched', ['app' => 'user_saml', 'user' => $user->getUID()]);
			} else {
				$this->logger->debug('Display name not updated', [
					'app' => 'user_saml',
					'user' => $user->getUID(),
					'newDisplayname' => $newDisplayname,
					'currentDisplayname' => $currentDisplayname,
				]);
			}

			if ($newQuota !== null) {
				$user->setQuota($newQuota);
				$this->logger->debug('Quota updated', ['app' => 'user_saml', 'user' => $user->getUID()]);
			} else {
				$this->logger->debug('Quota not updated', [
					'app' => 'user_saml',
					'user' => $user->getUID(),
				]);
			}

			$this->groupManager->handleIncomingGroups($user, $newGroups ?? []);
			$this->logger->debug('Incoming groups updated', [
				'app' => 'user_saml',
				'user' => $user->getUID(),
				'groups' => $newGroups,
			]);
		}
	}

	public function countUsers() {
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('uid'))
			->from('user_saml_users');
		$result = $query->executeQuery();

		return $result->fetchColumn();
	}
}
