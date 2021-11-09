<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\AppInfo;

use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\SabrePluginEvent;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('user_saml', $urlParams);
		$container = $this->getContainer();

		/**
		 * Middleware
		 */
		$container->registerService('OnlyLoggedInMiddleware', function (IAppContainer $c) {
			return new OnlyLoggedInMiddleware(
				$c->query('ControllerMethodReflector'),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('ServerContainer')->getUrlGenerator()
			);
		});

		$container->registerService(DavPlugin::class, function (IAppContainer $c) {
			$server = $c->getServer();
			return new DavPlugin(
				$server->getSession(),
				$server->getConfig(),
				$_SERVER,
				$server->get(SAMLSettings::class)
			);
		});

		$container->registerMiddleWare('OnlyLoggedInMiddleware');
		$this->timezoneHandling();
	}

	public function registerDavAuth() {
		$container = $this->getContainer();

		$dispatcher = $container->getServer()->getEventDispatcher();
		$dispatcher->addListener('OCA\DAV\Connector\Sabre::addPlugin', function (SabrePluginEvent $event) use ($container) {
			$event->getServer()->addPlugin($container->query(DavPlugin::class));
		});
	}

	private function timezoneHandling() {
		$container = $this->getContainer();

		$userSession = $container->getServer()->getUserSession();
		$session = $container->getServer()->getSession();
		$config = $container->getServer()->getConfig();

		$dispatcher = $container->getServer()->getEventDispatcher();
		$dispatcher->addListener('OCA\Files::loadAdditionalScripts', function () use ($session, $config, $userSession) {
			if (!$userSession->isLoggedIn()) {
				return;
			}

			$user = $userSession->getUser();
			$timezoneDB = $config->getUserValue($user->getUID(), 'core', 'timezone', '');

			if ($timezoneDB === '' || !$session->exists('timezone')) {
				\OCP\Util::addScript('user_saml', 'timezone');
			}
		});
	}
}
