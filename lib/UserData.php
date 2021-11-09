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
use OCP\IConfig;

class UserData {
	private $uid;
	/** @var array */
	private $attributes;
	/** @var UserResolver */
	private $userResolver;
	/** @var SAMLSettings */
	private $samlSettings;
	/** @var IConfig */
	private $config;

	public function __construct(UserResolver $userResolver, SAMLSettings $samlSettings, IConfig $config) {
		$this->userResolver = $userResolver;
		$this->samlSettings = $samlSettings;
		$this->config = $config;
	}

	public function setAttributes(array $attributes): void {
		$this->attributes = $attributes;
		$this->uid = null; // clear the state in case
	}

	public function getAttributes(): array {
		$this->assertIsInitialized();
		return $this->attributes;
	}

	public function hasUidMappingAttribute(): bool {
		$this->assertIsInitialized();
		$attribute = $this->getUidMappingAttribute();
		return $attribute !== null && isset($this->attributes[$attribute]);
	}

	public function getOriginalUid(): string {
		$this->assertIsInitialized();
		return $this->extractSamlUserId();
	}

	public function getEffectiveUid(): string {
		if($this->uid !== null) {
			return $this->uid;
		}
		$this->assertIsInitialized();
		try {
			$uid = $this->extractSamlUserId();
			$uid = $this->testEncodedObjectGUID($uid);
			$uid = $this->userResolver->findExistingUserId($uid, true);
			$this->uid = $uid;
		} catch (NoUserFoundException $e) {
			return '';
		}
		return $uid;
	}

	protected function extractSamlUserId(): string {
		$uidMapping = $this->getUidMappingAttribute();
		if($uidMapping !== null && isset($this->attributes[$uidMapping])) {
			if (is_array($this->attributes[$uidMapping])) {
				return trim($this->attributes[$uidMapping][0]);
			} else {
				return trim($this->attributes[$uidMapping]);
			}
		}
		return '';
	}

	/**
	 * returns the plain text UUID if the provided $uid string is a
	 * base64-encoded binary string representing e.g. the objectGUID. Otherwise
	 *
	 */
	public function testEncodedObjectGUID(string $uid): string {
		if (preg_match('/[^a-zA-Z0-9=+\/]/', $uid) !== 0) {
			// certainly not encoded
			return $uid;
		}

		$candidate = base64_decode($uid, true);
		if($candidate === false) {
			return $uid;
		}
		$candidate = $this->convertObjectGUID2Str($candidate);
		// the regex only matches the structure of the UUID, not its semantic
		// (i.e. version or variant) simply to be future compatible
		if(preg_match('/^[a-f0-9]{8}(-[a-f0-9]{4}){4}[a-f0-9]{8}$/i', $candidate) === 1) {
			$uid = $candidate;
		}
		return $uid;
	}

	/**
	 * @see \OCA\User_LDAP\Access::convertObjectGUID2Str
	 */
	protected function convertObjectGUID2Str($oguid): string {
		$hex_guid = bin2hex($oguid);
		$hex_guid_to_guid_str = '';
		for($k = 1; $k <= 4; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);

		return strtoupper($hex_guid_to_guid_str);
	}

	protected function assertIsInitialized() {
		if($this->attributes === null) {
			throw new \LogicException('UserData have to be initialized with setAttributes first');
		}
	}

	protected function getProviderSettings(): array {
		return $this->samlSettings->get($this->samlSettings->getProviderId());
	}

	protected function getUidMappingAttribute(): ?string {
		return $this->getProviderSettings()['general-uid_mapping'] ?? null;
	}
}
