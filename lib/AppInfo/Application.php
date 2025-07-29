<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AppInfo;

use OC\Security\CSRF\CsrfTokenManager;
use OC\User\LoginException;
use OC_User;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\Listener\LoadAdditionalScriptsListener;
use OCA\User_SAML\Listener\SabrePluginEventListener;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap {
	public function __construct(array $urlParams = []) {
		parent::__construct('user_saml', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerMiddleware(OnlyLoggedInMiddleware::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadAdditionalScriptsListener::class);
		$context->registerEventListener(SabrePluginAddEvent::class, SabrePluginEventListener::class);
		$context->registerService(DavPlugin::class, fn (ContainerInterface $c) => new DavPlugin(
			$c->get(ISession::class),
			$c->get(IConfig::class),
			$_SERVER,
			$c->get(SAMLSettings::class)
		));
	}

	public function boot(IBootContext $context): void {
		try {
			$context->injectFn(function (
				IL10N $l10n,
				IURLGenerator $urlGenerator,
				IConfig $config,
				IRequest $request,
				IUserSession $userSession,
				ISession $session,
				IFactory $factory,
				SAMLSettings $samlSettings,
				IUserManager $userManager,
				IDBConnection $connection,
				LoggerInterface $logger,
				GroupManager $groupManager,
				IEventDispatcher $dispatcher,
				CsrfTokenManager $csrfTokenManager,
				bool $isCLI,
			): void {
				$groupBackend = Server::get(GroupBackend::class);
				Server::get(IGroupManager::class)->addBackend($groupBackend);

				$userBackend = Server::get(UserBackend::class);

				$userBackend->registerBackends($userManager->getBackends());
				OC_User::useBackend($userBackend);

				$params = [];

				// Setting up the one login config may fail, if so, do not catch the requests later.
				switch ($config->getAppValue('user_saml', 'type')) {
					case 'saml':
						$type = 'saml';
						break;
					case 'environment-variable':
						$type = 'environment-variable';
						break;
					default:
						return;
				}

				if ($type === 'environment-variable') {
					// We should ignore oauth2 token endpoint (oauth can send the credentials as basic auth which will fail with apache auth)
					$uri = $request->getRequestUri();
					if (str_ends_with($uri, '/apps/oauth/api/v1/token')) {
						return;
					}

					try {
						OC_User::handleApacheAuth();
					} catch (LoginException $e) {
						if ($request->getPathInfo() === '/apps/user_saml/saml/error') {
							return;
						}
						$targetUrl = $urlGenerator->linkToRouteAbsolute(
							'user_saml.SAML.genericError',
							[
								'message' => $e->getMessage()
							]
						);
						header('Location: ' . $targetUrl);
						exit();
					}
				}

				$redirectSituation = false;

				$user = $userSession->getUser();
				if ($user !== null) {
					$enabled = $user->isEnabled();
					if ($enabled === false) {
						if ($request->getPathInfo() === '/apps/user_saml/saml/error') {
							return;
						}
						$targetUrl = $urlGenerator->linkToRouteAbsolute(
							'user_saml.SAML.genericError',
							[
								'message' => $l10n->t('This user account is disabled, please contact your administrator.')
							]
						);
						header('Location: ' . $targetUrl);
						exit();
					}
				}

				// All requests that are not authenticated and match against the "/login" route are
				// redirected to the SAML login endpoint
				if (!$isCLI
					&& !$userSession->isLoggedIn()
					&& ($request->getPathInfo() === '/login')) {
					try {
						$params = $request->getParams();
					} catch (\LogicException) {
						// ignore exception when PUT is called since getParams cannot parse parameters in that case
					}
					if (isset($params['direct']) && ($params['direct'] === 1 || $params['direct'] === '1')) {
						return;
					}
					$redirectSituation = true;
				}

				$multipleUserBackEnds = $samlSettings->allowMultipleUserBackEnds();
				$configuredIdps = $samlSettings->getListOfIdps();
				$showLoginOptions = $type !== 'environment-variable' && ($multipleUserBackEnds || count($configuredIdps) > 1);

				if ($redirectSituation === true && $showLoginOptions) {
					try {
						$params = $request->getParams();
					} catch (\LogicException) {
						// ignore exception when PUT is called since getParams cannot parse parameters in that case
					}
					$redirectUrl = '';
					if (isset($params['redirect_url'])) {
						$redirectUrl = $params['redirect_url'];
					}

					$targetUrl = $urlGenerator->linkToRouteAbsolute(
						'user_saml.SAML.selectUserBackEnd',
						[
							'redirectUrl' => $redirectUrl
						]
					);
					header('Location: ' . $targetUrl);
					exit();
				}

				if ($redirectSituation === true) {
					try {
						$params = $request->getParams();
					} catch (\LogicException) {
						// ignore exception when PUT is called since getParams cannot parse parameters in that case
					}
					$originalUrl = '';
					if (isset($params['redirect_url'])) {
						$originalUrl = $urlGenerator->getAbsoluteURL($params['redirect_url']);
					}

					$csrfToken = $csrfTokenManager->getToken();
					$targetUrl = $urlGenerator->linkToRouteAbsolute(
						'user_saml.SAML.login',
						[
							'requesttoken' => $csrfToken->getEncryptedValue(),
							'originalUrl' => $originalUrl,
							'idp' => array_keys($configuredIdps)[0] ?? '',
						]
					);
					header('Location: ' . $targetUrl);
					exit();
				}
			});
		} catch (Throwable $e) {
			Server::get(LoggerInterface::class)->critical('Error when loading user_saml app', [
				'exception' => $e,
			]);
		}
	}
}
