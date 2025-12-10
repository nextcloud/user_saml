<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Listener;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ISession;

/** @template-implements IEventListener<SabrePluginAddEvent|Event> */
class SabrePluginEventListener implements IEventListener {
	public function __construct(
		private readonly ISession $session,
		private readonly IAppConfig $appConfig,
		private readonly SAMLSettings $settings,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof SabrePluginAddEvent) {
			return;
		}
		$event->getServer()->addPlugin(new DavPlugin(
			$this->session,
			$this->appConfig,
			$_SERVER,
			$this->settings,
		));
	}
}
