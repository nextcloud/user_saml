<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\LDAP\Exceptions\MultipleUsersReturnedException;
use OCP\LDAP\ILDAPProviderFactory;
use OCP\Server;

class UserResolver {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly ILDAPProviderFactory $ldapProviderFactory,
		private readonly IConfig $config,
	) {
	}

	/**
	 * @throws NoUserFoundException
	 */
	public function findExistingUserId(string $rawUidCandidate, ?array $idpSettings = null, bool $force = false, bool $isActiveDirectory = false): string {
		// If configured, find the user based on a different LDAP attribute.
		if ($idpSettings !== null
			&& version_compare($this->config->getSystemValueString('version', '0.0.0'), '34.0.0', '>=')
			&& $this->ldapProviderFactory->isAvailable()
			&& isset($idpSettings['saml-attribute-mapping-user_id_ldap_mapping'])
			&& $idpSettings['saml-attribute-mapping-user_id_ldap_mapping'] !== null
			&& $idpSettings['saml-attribute-mapping-user_id_ldap_mapping'] !== '') {
			$userIdLdapMapping = $idpSettings['saml-attribute-mapping-user_id_ldap_mapping'];
			/** @psalm-suppress UndefinedClass only in NC 34 or above */
			try {
				if ($isActiveDirectory) {
					$rawUidCandidate = $this->formatGuid2ForFilterUser($rawUidCandidate);
				}

				if ($rawUidCandidate === '') {
					throw new NoUserFoundException('User id is empty');
				}

				/** @psalm-suppress UndefinedInterfaceMethod only in NC 34 or above */
				$user = $this->ldapProviderFactory->getLDAPProvider()->findOneUserByAttributeValue($userIdLdapMapping, $rawUidCandidate);
			} catch (MultipleUsersReturnedException $e) {
				return '';
			}
			if ($user !== null) {
				return $user->getUID();
			}

			// continue normal workflow
		}

		if ($force) {
			if ($isActiveDirectory) {
				$this->ensureUser($this->formatGuid2ForFilterUser($rawUidCandidate));
			} else {
				$this->ensureUser($rawUidCandidate);
			}
		}
		if ($this->userManager->userExists($rawUidCandidate)) {
			return $rawUidCandidate;
		}
		try {
			$sanitized = $this->sanitizeUserIdCandidate($rawUidCandidate);
		} catch (\InvalidArgumentException) {
			$sanitized = '';
		}
		if ($this->userManager->userExists($sanitized)) {
			return $sanitized;
		}
		throw new NoUserFoundException('User ' . $rawUidCandidate . ' not valid or not found');
	}

	/**
	 * @see \OCA\User_LDAP\Access::formatGuid2ForFilterUser
	 */
	private function formatGuid2ForFilterUser(string $guid): string {
		$blocks = explode('-', $guid);
		if (count($blocks) !== 5) {
			/*
			 * Why not throw an Exception instead? This method is a utility
			 * called only when trying to figure out whether a "missing" known
			 * LDAP user was or was not renamed on the LDAP server. And this
			 * even on the use case that a reverse lookup is needed (UUID known,
			 * not DN), i.e. when finding users (search dialog, users page,
			 * login, …) this will not be fired. This occurs only if shares from
			 * a users are supposed to be mounted who cannot be found. Throwing
			 * an exception here would kill the experience for a valid, acting
			 * user. Instead, we write a log message.
			 */
			\OCP\Log\logger()->info(
				'Passed string does not resemble a valid GUID. Known UUID '
				. '({uuid}) probably does not match UUID configuration.',
				['app' => 'user_saml', 'uuid' => $guid]
			);
			return $guid;
		}
		for ($i = 0; $i < 3; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$pairs = array_reverse($pairs);
			$blocks[$i] = implode('', $pairs);
		}
		for ($i = 0; $i < 5; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$blocks[$i] = '\\' . implode('\\', $pairs);
		}
		return implode('', $blocks);
	}

	/**
	 * @throws NoUserFoundException
	 */
	public function findExistingUser(string $rawUidCandidate): IUser {
		$uid = $this->findExistingUserId($rawUidCandidate);
		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new NoUserFoundException('User ' . $rawUidCandidate . ' not valid or not found.');
		}
		return $user;
	}

	public function userExists(string $uid, array $idpSettings, bool $force = false): bool {
		try {
			$this->findExistingUserId($uid, $idpSettings, $force);
			return true;
		} catch (NoUserFoundException) {
			return false;
		}
	}

	protected function ensureUser(string $search): void {
		$this->userManager->search($search);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function sanitizeUserIdCandidate(string $rawUidCandidate): string {
		//FIXME: adjusted copy of LDAP's Access::sanitizeUsername(), should go to API
		$sanitized = trim($rawUidCandidate);

		// Use htmlentities to get rid of accents
		$sanitized = htmlentities($sanitized, ENT_NOQUOTES, 'UTF-8');

		// Remove accents
		$sanitized = preg_replace('#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $sanitized);
		// Remove ligatures
		$sanitized = preg_replace('#&([A-Za-z]{2})(?:lig);#', '\1', (string)$sanitized);
		// Remove unknown leftover entities
		$sanitized = preg_replace('#&[^;]+;#', '', (string)$sanitized);

		// Replacements
		$sanitized = str_replace(' ', '_', (string)$sanitized);

		// Every remaining disallowed characters will be removed
		$sanitized = (string)preg_replace('/[^a-zA-Z0-9_.@-]/u', '', $sanitized);

		if ($sanitized === '') {
			throw new \InvalidArgumentException('provided name template for username does not contain any allowed characters');
		}

		return $sanitized;
	}
}
