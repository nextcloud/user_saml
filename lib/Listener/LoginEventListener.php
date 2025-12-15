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
use OCP\User\Events\UserLoggedInEvent;

/** @template-implements IEventListener<UserLoggedInEvent|Event> */
class LoginEventListener implements IEventListener {
	public function __construct(
		protected SessionService $sessionService,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserLoggedInEvent
			|| $event->isTokenLogin()
			|| !$this->sessionService->isActiveSamlSession()
		) {
			return;
		}
		$this->sessionService->storeSessionDataInDatabase();
	}
}
