<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Listener;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Util;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent|Event> */
class LoadAdditionalScriptsListener implements IEventListener {
	public function __construct(
		private readonly ISession $session,
		private readonly IUserSession $userSession,
		private readonly IConfig $config,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeTemplateRenderedEvent) {
			return;
		}

		if (!$event->isLoggedIn()) {
			return;
		}

		$user = $this->userSession->getUser();
		$timezoneDB = $this->config->getUserValue($user->getUID(), 'core', 'timezone', '');

		if ($timezoneDB === '' || !$this->session->exists('timezone')) {
			Util::addScript('user_saml', 'vendor/jstz.min');
			Util::addScript('user_saml', 'timezone');
		}
	}
}
