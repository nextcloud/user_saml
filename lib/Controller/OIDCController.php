<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 * @copyright Copyright (c) 2018 Jean-Baptiste Pin <jibet.pin@gmail.com>
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

namespace OCA\User_OIDC\Controller;

use Firebase\JWT\JWT;
use OCA\User_OIDC\Exceptions\NoUserFoundException;
use OCA\User_OIDC\UserBackend;
use OCA\User_OIDC\OIDCClient;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Jumbojett\OpenIDConnectClient;

class OIDCController extends Controller {
	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;
	/** @var UserBackend */
	private $userBackend;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserManager */
	private $userManager;
	/** @var ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param UserBackend $userBackend
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 */
	public function __construct($appName,
								IRequest $request,
								ISession $session,
								IUserSession $userSession,
								UserBackend $userBackend,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								ILogger $logger) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->userSession = $userSession;
		$this->userBackend = $userBackend;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * @param array $userData
	 * @throws NoUserFoundException
	 */
	private function autoprovisionIfPossible(array $userData) {

		$uidMapping = $this->config->getSystemValue('user_oidc' ,'map_uid', 'uid');
		if(isset($userData[$uidMapping])) {
			if(is_array($userData[$uidMapping])) {
				$uid = $userData[$uidMapping][0];
			} else {
				$uid = $userData[$uidMapping];
			}

			// make sure that a valid UID is given
			if (empty($uid)) {
				$this->logger->error('Uid "' . $uid . '" is not a valid uid please check your attribute mapping', ['app' => $this->appName]);
				throw new \InvalidArgumentException('No valid uid given, please check your attribute mapping. Given uid: ' . $uid);
			}

			// if this server acts as a global scale master and the user is not
			// a local admin of the server we just create the user and continue
			// no need to update additional attributes
			$isGsEnabled = $this->config->getSystemValue('gs.enabled', false);
			$isGsMaster = $this->config->getSystemValue('gss.mode', 'slave') === 'master';
			$isGsMasterAdmin = in_array($uid, $this->config->getSystemValue('gss.master.admin', []));
			if ($isGsEnabled && $isGsMaster && !$isGsMasterAdmin) {
				$this->userBackend->createUserIfNotExists($uid);
				return;
			}
			$userExists = $this->userManager->userExists($uid);
			$autoProvisioningAllowed = $this->userBackend->autoprovisionAllowed();
			if($userExists === true) {
				if($autoProvisioningAllowed) {
					$this->userBackend->updateAttributes($uid, $userData);
				}
				return;
			}

			if(!$userExists && !$autoProvisioningAllowed) {
				// it is possible that the user was not logged in before and
				// thus is not known to the original backend. A search can
				// help with it and make the user known
				$this->userManager->search($uid);
				if($this->userManager->userExists($uid)) {
					return;
				}
				throw new NoUserFoundException('Auto provisioning not allowed and user ' . $uid . ' does not exist');
			} elseif(!$userExists && $autoProvisioningAllowed) {
				$this->userBackend->createUserIfNotExists($uid, $userData);
				$this->userBackend->updateAttributes($uid, $userData);
				return;
			}
		}

		throw new NoUserFoundException('IDP parameter for the UID (' . $uidMapping . ') not found. Possible parameters are: ' . json_encode(array_keys($userData)));
	}
    
	/**
	 * @PublicPage
	 * @UseSession
	 * @OnlyUnauthenticatedUsers
	 *
	 * @return Http\RedirectResponse
	 * @throws \Exception
	 */
	public function login() {
		$authUrl = $this->config->getSystemValue('user_oidc', 'auth_url', 'localhost');
		$clientId = $this->config->getSystemValue('user_oidc', 'client_id', '');
		$clientSecret = $this->config->getSystemValue('user_oidc', 'client_secret', '');
		$oidc = new OpenIDConnectClient($authUrl, $clientId, $clientSecret);
		$scopes = $this->config->getSystemValue('user_oidc', 'scopes', array('openid'));
		$oidc->addScope($scopes);
		$redirectUrl = $this->request->getParam('originalUrl', '');
        if (empty($redirectUrl)) {
            $redirectUrl = $this->urlGenerator->getAbsoluteURL('/');
		}
		$this->logger->debug('Using redirectUrl ' . $redirectUrl, ['app' => $this->appName]);
		$oidc->setRedirectUrl($redirectUrl);
		$this->session->set('user_oidc.originalUrl', $redirectUrl);
		$oidc->authenticate();
		$this->session->set('user_oidc.accessToken', $oidc->getAccessToken());
		$this->session->set('user_oidc.subClaim', $oidc->getVerifiedClaims('sub'));
		$this->session->set('user_oidc.userInfo', $oidc->requestUserInfo());
        try {
            $this->autoprovisionIfPossible($this->session->get('user_oidc.userInfo'));
            $user = $this->userManager->get($this->userBackend->getCurrentUserId());
            if(!($user instanceof IUser)) {
                throw new NoUserFoundException('User' . $this->userBackend->getCurrentUserId() . ' not valid or not found');
            }
            $user->updateLastLoginTimestamp();
        } catch (NoUserFoundException $e) {
            if ($e->getMessage()) {
                $this->logger->warning('Error while trying to login using sso environment variable: ' . $e->getMessage(), ['app' => 'user_oidc']);
            }
            $redirectUrl = $this->urlGenerator->linkToRouteAbsolute('user_oidc.OIDC.notProvisioned');
        }

		return new Http\RedirectResponse($redirectUrl);
	}

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Http\RedirectResponse
	 * @throws Error
	 */
	public function signOut() {

		$pass = $this->request->passesCSRFCheck();
		$isGlobalScaleEnabled = $this->config->getSystemValue('gs.enabled', false);
		$gssMode = $this->config->getSystemValue('gss.mode', '');
		if (!$pass && $isGlobalScaleEnabled && $gssMode === 'master') {
			$jwt = $this->request->getParam('jwt', '');
			$pass = $this->isValidJwt($jwt);
		}

		if($pass) {
			$authUrl = $this->config->getSystemValue('user_oidc', 'auth_url', 'localhost');
			$clientId = $this->config->getSystemValue('user_oidc', 'client_id', '');
			$clientSecret = $this->config->getSystemValue('user_oidc', 'client_secret', '');
            $oidc = new OpenIDConnectClient($authUrl, $clientId, $clientSecret);
			$returnTo = null;
			$accessToken = $this->session->get('user_oidc.accessToken');
			$this->userSession->logout();
			$targetUrl = $oidc->signOut($accessToken, $returnTo);
		} else {
			$targetUrl = $this->urlGenerator->getAbsoluteURL('/');
		}

		return new Http\RedirectResponse($targetUrl);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function notProvisioned() {
		return new Http\TemplateResponse($this->appName, 'notProvisioned', [], 'guest');
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 * @param string $message
	 * @return Http\TemplateResponse
	 */
	public function genericError($message) {
		if (empty($message)) {
			$message = 'Unknown error, please check the log file for more details.';
		}
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}

    private function isValidJwt($jwt) {
		try {
			$key = $this->config->getSystemValue('gss.jwt.key', '');
			JWT::decode($jwt, $key, ['RS256']);
		} catch (\Exception $e) {
			return false;
		}

		return true;
    }
    
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Http\TemplateResponse
	 */
	public function base() {
		$message = 'This page should not be visited directly.';
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}

}
