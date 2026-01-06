<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Model;

use OCP\ISession;

class SessionData implements \JsonSerializable, \Stringable {
	public const KEY_IDENTITY_PROVIDER_ID = 'user_saml.Idp';
	public const KEY_SAML_NAME_ID = 'user_saml.samlNameId';
	public const KEY_SAML_NAME_ID_FORMAT = 'user_saml.samlNameIdFormat';
	public const KEY_SAML_NAME_ID_QUALIFIER = 'user_saml.samlNameIdNameQualifier';
	public const KEY_SAML_NAME_ID_SP_QUALIFIER = 'user_saml.samlNameIdSPNameQualifier';
	public const KEY_SAML_SESSION_INDEX = 'user_saml.samlSessionIndex';

	public const SESSION_KEYS = [
		'KEY_IDENTITY_PROVIDER_ID' => 'user_saml.Idp',
		'KEY_SAML_NAME_ID' => 'user_saml.samlNameId',
		'KEY_SAML_NAME_ID_FORMAT' => 'user_saml.samlNameIdFormat',
		'KEY_SAML_NAME_ID_QUALIFIER' => 'user_saml.samlNameIdNameQualifier',
		'KEY_SAML_NAME_ID_SP_QUALIFIER' => 'user_saml.samlNameIdSPNameQualifier',
		'KEY_SAML_SESSION_INDEX' => 'user_saml.samlSessionIndex',
	];

	public function __construct(
		protected int $identityProviderId,
		protected string $samlNameId,
		protected string $samlNameIdFormat,
		protected string $samlNameIdNameQualifier,
		protected string $samlNameIdSPNameQualifier,
		protected ?string $samlSessionIndex,
	) {
		if ($this->identityProviderId < 0) {
			throw new \InvalidArgumentException('IdentityProviderId has to be a positive integer');
		}
	}

	public function storeInSession(ISession $session): void {
		foreach ($this->jsonSerialize() as $sessionKey => $value) {
			$session->set($sessionKey, $value);
		}
	}

	public function jsonSerialize(): array {
		return [
			self::KEY_IDENTITY_PROVIDER_ID => $this->identityProviderId,
			self::KEY_SAML_NAME_ID => $this->samlNameId,
			self::KEY_SAML_NAME_ID_FORMAT => $this->samlNameIdFormat,
			self::KEY_SAML_NAME_ID_QUALIFIER => $this->samlNameIdNameQualifier,
			self::KEY_SAML_NAME_ID_SP_QUALIFIER => $this->samlNameIdSPNameQualifier,
			self::KEY_SAML_SESSION_INDEX => $this->samlSessionIndex,
		];
	}

	public static function fromInputArray(array $rawData): self {
		if (!isset($rawData[self::KEY_IDENTITY_PROVIDER_ID])) {
			throw new \InvalidArgumentException('Expected and required Identity Provider ID is missing');
		}

		return new self(
			$rawData[self::KEY_IDENTITY_PROVIDER_ID],
			$rawData[self::KEY_SAML_NAME_ID] ?? '',
			$rawData[self::KEY_SAML_NAME_ID_FORMAT] ?? '',
			$rawData[self::KEY_SAML_NAME_ID_QUALIFIER] ?? '',
			$rawData[self::KEY_SAML_NAME_ID_SP_QUALIFIER] ?? '',
			$rawData[self::KEY_SAML_SESSION_INDEX] ?? null,
		);
	}

	public static function fromSession(ISession $session): self {
		$retrievedData = [];
		foreach (self::SESSION_KEYS as $sessionKey) {
			$value = $session->get($sessionKey);
			if ($value === null) {
				continue;
			}
			$retrievedData[$sessionKey] = $session->get($sessionKey);
		}
		return self::fromInputArray($retrievedData);
	}

	public function __toString(): string {
		return \json_encode($this);
	}
}
