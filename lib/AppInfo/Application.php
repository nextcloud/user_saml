<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AppInfo;

use OC\Security\CSRF\CsrfTokenManager;
use OC_User;
use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\Listener\LoadAdditionalScriptsListener;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

require_once __DIR__ . '/../../3rdparty/vendor/autoload.php';

class Application extends App implements IBootstrap {
	public function __construct(array $urlParams = []) {
		parent::__construct('user_saml', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerMiddleware(OnlyLoggedInMiddleware::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadAdditionalScriptsListener::class);
		$context->registerService(DavPlugin::class, function (ContainerInterface $c) {
			return new DavPlugin(
				$c->get(ISession::class),
				$c->get(IConfig::class),
				$_SERVER,
				$c->get(SAMLSettings::class)
			);
		});
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
			) {
				$userData = new UserData(
					new UserResolver($userManager),
					$samlSettings,
				);

				$userBackend = new UserBackend(
					$config,
					$urlGenerator,
					$session,
					$connection,
					$userManager,
					$groupManager,
					$samlSettings,
					$logger,
					$userData,
					$dispatcher
				);
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

					OC_User::handleApacheAuth();
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
				if (!$isCLI &&
					!$userSession->isLoggedIn() &&
					($request->getPathInfo() === '/login')) {
					try {
						$params = $request->getParams();
					} catch (\LogicException $e) {
						// ignore exception when PUT is called since getParams cannot parse parameters in that case
					}
					if (isset($params['direct']) && ($params['direct'] === 1 || $params['direct'] === '1')) {
						return;
					}
					$redirectSituation = true;
				}

				// If a request to OCS or remote.php is sent by the official desktop clients it can
				// be intercepted as it supports SAML. All other clients don't yet and thus we
				// require the usage of application specific passwords there.
				//
				// However, it is an opt-in setting to use SAML for the desktop clients. For better
				// UX (users don't have to reauthenticate) we default to disallow the access via
				// SAML at the moment.
				$useSamlForDesktopClients = $config->getAppValue('user_saml', 'general-use_saml_auth_for_desktop', '0');
				if ($useSamlForDesktopClients === '1') {
					$currentUrl = substr(explode('?', $request->getRequestUri(), 2)[0], strlen($urlGenerator->getWebroot()));
					if (substr($currentUrl, 0, 12) === '/remote.php/' || substr($currentUrl, 0, 5) === '/ocs/') {
						if (!$userSession->isLoggedIn() && $request->isUserAgent([\OCP\IRequest::USER_AGENT_CLIENT_DESKTOP])) {
							$redirectSituation = true;

							if (preg_match('/^.*\/(\d+\.\d+\.\d+).*$/', $request->getHeader('USER_AGENT'), $matches) === 1) {
								$versionString = $matches[1];

								if (version_compare($versionString, '2.5.0', '>=') === true) {
									$redirectSituation = false;
								}
							}
						}
					}
				}

				$multipleUserBackEnds = $samlSettings->allowMultipleUserBackEnds();
				$configuredIdps = $samlSettings->getListOfIdps();
				$showLoginOptions = $multipleUserBackEnds || count($configuredIdps) > 1;

				if ($redirectSituation === true && $showLoginOptions) {
					try {
						$params = $request->getParams();
					} catch (\LogicException $e) {
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
					} catch (\LogicException $e) {
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
			\OCP\Server::get(LoggerInterface::class)->critical('Error when loading user_saml app', [
				'exception' => $e,
			]);
		}
	}
}
