<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AlternativeLogin;

use OCP\Authentication\IAlternativeLogin;

class AlternativeLogin implements IAlternativeLogin {
	public function __construct(
		private readonly string $name,
		private readonly string $href,
	) {
	}

	#[\Override]
	public function getLabel(): string {
		return $this->name;
	}

	#[\Override]
	public function getLink(): string {
		return $this->href;
	}

	#[\Override]
	public function getClass(): string {
		return '';
	}

	#[\Override]
	public function load(): void {
	}
}
