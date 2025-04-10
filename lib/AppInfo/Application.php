<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\SabrePluginEvent;
use OCP\Server;
use OCP\Util;
use Psr\Container\ContainerInterface;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('user_saml', $urlParams);
		$container = $this->getContainer();

		/**
		 * Middleware
		 */
		$container->registerService('OnlyLoggedInMiddleware', function (ContainerInterface $c) {
			return new OnlyLoggedInMiddleware(
				$c->get(IControllerMethodReflector::class),
				$c->get(IUserSession::class),
				$c->get(IURLGenerator::class)
			);
		});

		$container->registerService(DavPlugin::class, function (ContainerInterface $c) {
			return new DavPlugin(
				$c->get(ISession::class),
				$c->get(IConfig::class),
				$_SERVER,
				$c->get(SAMLSettings::class)
			);
		});

		$container->registerMiddleWare('OnlyLoggedInMiddleware');
		$this->timezoneHandling();
	}

	public function registerDavAuth(): void {
		$dispatcher = Server::get(IEventDispatcher::class);
		$dispatcher->addListener('OCA\DAV\Connector\Sabre::addPlugin', function (SabrePluginEvent $event) {
			$event->getServer()->addPlugin(Server::get(DavPlugin::class));
		});
	}

	private function timezoneHandling(): void {
		$userSession = Server::get(IUserSession::class);
		$session = Server::get(ISession::class);
		$config = Server::get(IConfig::class);

		$dispatcher = Server::get(IEventDispatcher::class);
		$dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () use ($session, $config, $userSession) {
			if (!$userSession->isLoggedIn()) {
				return;
			}

			$user = $userSession->getUser();
			$timezoneDB = $config->getUserValue($user->getUID(), 'core', 'timezone', '');

			if ($timezoneDB === '' || !$session->exists('timezone')) {
				Util::addScript('user_saml', 'vendor/jstz.min');
				Util::addScript('user_saml', 'timezone');
			}
		});
	}
}
