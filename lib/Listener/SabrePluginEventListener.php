<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Listener;

use OCA\User_SAML\DavPlugin;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\SabrePluginEvent;
use OCP\Server;

/** @template-implements IEventListener<SabrePluginEvent|Event> */
class SabrePluginEventListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof SabrePluginEvent) {
			return;
		}
		$event->getServer()->addPlugin(Server::get(DavPlugin::class));
	}
}
