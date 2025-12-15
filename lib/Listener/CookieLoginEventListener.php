<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Listener;

use OCA\User_SAML\Service\SessionService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\BeforeUserLoggedInWithCookieEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;

/** @template-implements IEventListener<BeforeUserLoggedInWithCookieEvent|UserLoggedInWithCookieEvent|Event> */
class CookieLoginEventListener implements IEventListener {
	protected ?string $oldSessionId = null;

	public function __construct(
		protected readonly SessionService $sessionService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeUserLoggedInWithCookieEvent) {
			$this->prepareRestoreOfSession();
			return;
		}

		if ($event instanceof UserLoggedInWithCookieEvent) {
			$this->restoreSession();
			return;
		}
	}

	protected function prepareRestoreOfSession(): void {
		if (isset($_COOKIE['nc_session_id'])) {
			$this->oldSessionId = $_COOKIE['nc_session_id'];
		}
	}

	protected function restoreSession(): void {
		if ($this->oldSessionId !== null) {
			$this->sessionService->restoreSession($this->oldSessionId);
		}
	}
}
