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
