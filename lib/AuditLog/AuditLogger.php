<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AuditLog;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class AuditLogger {

	public function __construct(
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * Does nothing on servers before Nextcloud 36, whose CriticalActionPerformedEvent has no operation
	 *
	 * @param array<string, mixed> $parameters named values for the placeholders of $message, in order, optionally followed by more context
	 */
	public function log(Operation $operation, string $message, array $parameters = []): void {
		if (!method_exists(CriticalActionPerformedEvent::class, 'getOperation')) {
			return;
		}
		$arguments = [$message, $parameters, false, $operation->value];
		$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(...$arguments));
	}

	public function providerCreated(int $providerId): void {
		$this->log(Operation::ProviderCreated, 'SAML identity provider with ID %s was created', [
			'providerId' => $providerId,
		]);
	}

	/**
	 * Only logs which settings changed, never their values, as they include certificates and private keys
	 *
	 * @param array<string, mixed> $settingsBefore
	 * @param array<string, mixed> $settingsAfter
	 */
	public function providerUpdated(int $providerId, array $settingsBefore, array $settingsAfter): void {
		$changedSettings = [];
		foreach ($settingsAfter as $key => $value) {
			if (!array_key_exists($key, $settingsBefore) || $settingsBefore[$key] !== $value) {
				$changedSettings[] = $key;
			}
		}
		foreach ($settingsBefore as $key => $value) {
			if (!array_key_exists($key, $settingsAfter)) {
				$changedSettings[] = $key;
			}
		}
		if ($changedSettings === []) {
			return;
		}

		$this->log(Operation::ProviderUpdated, 'SAML identity provider with ID %s was updated', [
			'providerId' => $providerId,
			'changedSettings' => $changedSettings,
		]);
	}

	public function providerDeleted(int $providerId): void {
		$this->log(Operation::ProviderDeleted, 'SAML identity provider with ID %s was deleted', [
			'providerId' => $providerId,
		]);
	}

	/**
	 * @param 'login'|'global_scale'|'occ' $source
	 */
	public function userProvisioned(string $userId, ?int $providerId, string $source): void {
		$this->log(Operation::UserProvisioned, 'User %s was provisioned by SAML identity provider %s', [
			'userId' => $userId,
			'providerId' => $providerId,
			'source' => $source,
		]);
	}

	/**
	 * Never pass the SAML response or attribute values
	 *
	 * @param array $errors error codes reported by the SAML toolkit
	 */
	public function loginFailed(string $reason, ?int $providerId, array $errors = []): void {
		$this->log(Operation::LoginFailed, 'SAML login failed: %s', [
			'reason' => $reason,
			'providerId' => $providerId,
			'errors' => $errors,
		]);
	}

	/**
	 * @param 'idp'|'global_scale' $initiator
	 */
	public function sessionRevoked(string $userId, ?int $providerId, string $initiator): void {
		$this->log(Operation::SessionRevoked, 'Session of user %s was ended by a single logout from SAML identity provider %s', [
			'userId' => $userId,
			'providerId' => $providerId,
			'initiator' => $initiator,
		]);
	}
}
