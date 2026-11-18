<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AuditLog;

/**
 * Operations SSO & SAML authentication writes to the admin audit log, in the
 * form `app.entity.action`
 */
enum Operation: string {
	case ProviderCreated = 'user_saml.provider.created';
	case ProviderUpdated = 'user_saml.provider.updated';
	case ProviderDeleted = 'user_saml.provider.deleted';
	case UserProvisioned = 'user_saml.user.provisioned';
	case LoginFailed = 'user_saml.login.failed';
	case SessionRevoked = 'user_saml.session.revoked';

	public function description(): string {
		return match ($this) {
			self::ProviderCreated => 'A SAML identity provider configuration was created',
			self::ProviderUpdated => 'The configuration of a SAML identity provider was changed',
			self::ProviderDeleted => 'A SAML identity provider configuration was deleted',
			self::UserProvisioned => 'An account was created for a user of an identity provider at login or with the saml:user:add command',
			self::LoginFailed => 'A SAML login was rejected, for example because of an invalid response or signature, an unknown account or a user filter',
			self::SessionRevoked => 'An identity provider or a Global Scale master ended a session of a user through a single logout',
		};
	}
}
