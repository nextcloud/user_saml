<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCP\IUser;
use OCP\IUserManager;

class UserResolver {
	/** @var IUserManager */
	private $userManager;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
	}

	/**
	 * @throws NoUserFoundException
	 */
	public function findExistingUserId(string $rawUidCandidate, bool $force = false): string {
		if($force) {
			$this->ensureUser($rawUidCandidate);
		}
		if($this->userManager->userExists($rawUidCandidate)) {
			return $rawUidCandidate;
		}
		try {
			$sanitized = $this->sanitizeUserIdCandidate($rawUidCandidate);
		} catch(\InvalidArgumentException $e) {
			$sanitized = '';
		}
		if($this->userManager->userExists($sanitized)) {
			return $sanitized;
		}
		throw new NoUserFoundException('User' . $rawUidCandidate . ' not valid or not found');
	}

	/**
	 * @throws NoUserFoundException
	 */
	public function findExistingUser(string $rawUidCandidate): IUser {
		$uid = $this->findExistingUserId($rawUidCandidate);
		$user = $this->userManager->get($uid);
		if($user === null) {
			throw new NoUserFoundException('User' . $rawUidCandidate . ' not valid or not found');
		}
		return $user;
	}

	public function userExists(string $uid, bool $force = false): bool {
		try {
			$this->findExistingUserId($uid, $force);
			return true;
		} catch(NoUserFoundException $e) {
			return false;
		}
	}

	protected function ensureUser($search) {
		$this->userManager->search($search);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function sanitizeUserIdCandidate(string $rawUidCandidate): string {
		//FIXME: adjusted copy of LDAP's Access::sanitizeUsername(), should go to API
		$sanitized = trim($rawUidCandidate);

		// Transliteration to ASCII
		$transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $sanitized);
		if($transliterated !== false) {
			// depending on system config iconv can work or not
			$sanitized = $transliterated;
		}

		// Replacements
		$sanitized = str_replace(' ', '_', $sanitized);

		// Every remaining disallowed characters will be removed
		$sanitized = preg_replace('/[^a-zA-Z0-9_.@-]/u', '', $sanitized);

		if($sanitized === '') {
			throw new \InvalidArgumentException('provided name template for username does not contain any allowed characters');
		}

		return $sanitized;
	}
}
