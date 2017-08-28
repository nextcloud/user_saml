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

namespace OCA\User_SAML\Controller;

use Firebase\JWT\JWT;
use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\ValidationError;

class SAMLController extends Controller {
	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;
	/** @var SAMLSettings */
	private $SAMLSettings;
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
	/** @var IL10N */
	private $l;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param SAMLSettings $SAMLSettings
	 * @param UserBackend $userBackend
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 * @param IL10N $l
	 */
	public function __construct($appName,
								IRequest $request,
								ISession $session,
								IUserSession $userSession,
								SAMLSettings $SAMLSettings,
								UserBackend $userBackend,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								ILogger $logger,
								IL10N $l) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->userSession = $userSession;
		$this->SAMLSettings = $SAMLSettings;
		$this->userBackend = $userBackend;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->l = $l;
	}

	/**
	 * @param array $auth
	 * @throws NoUserFoundException
	 */
	private function autoprovisionIfPossible(array $auth) {

		$prefix = $this->SAMLSettings->getPrefix();
		$uidMapping = $this->config->getAppValue('user_saml', $prefix . 'general-uid_mapping');
		if(isset($auth[$uidMapping])) {
			if(is_array($auth[$uidMapping])) {
				$uid = $auth[$uidMapping][0];
			} else {
				$uid = $auth[$uidMapping];
			}

			// make sure that a valid UID is given
			if (empty($uid)) {
				$this->logger->error('Uid "' . $uid . '" is not a valid uid please check your attribute mapping', ['app' => $this->appName]);
				throw new \InvalidArgumentException('No valid uid given, please check your attribute mapping. Given uid: ' . $uid);
			}

			$uid = $this->userBackend->testEncodedObjectGUID($uid);

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
					$this->userBackend->updateAttributes($uid, $auth);
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
				$this->userBackend->createUserIfNotExists($uid, $auth);
				$this->userBackend->updateAttributes($uid, $auth);
				return;
			}
		}

		throw new NoUserFoundException('IDP parameter for the UID (' . $uidMapping . ') not found. Possible parameters are: ' . json_encode(array_keys($auth)));
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @OnlyUnauthenticatedUsers
	 * @NoCSRFRequired
	 *
	 * @param int $idp id of the idp
	 * @return Http\RedirectResponse
	 * @throws \Exception
	 */
	public function login($idp) {
		$type = $this->config->getAppValue($this->appName, 'type');
		switch($type) {
			case 'saml':
				$auth = new Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
				$ssoUrl = $auth->login(null, [], false, false, true);
				$this->session->set('user_saml.AuthNRequestID', $auth->getLastRequestID());
				$this->session->set('user_saml.OriginalUrl', $this->request->getParam('originalUrl', ''));
				$this->session->set('user_saml.Idp', $idp);
				break;
			case 'environment-variable':
				$ssoUrl = $this->request->getParam('originalUrl', '');
				if (empty($ssoUrl)) {
					$ssoUrl = $this->urlGenerator->getAbsoluteURL('/');
				}
				$this->session->set('user_saml.samlUserData', $_SERVER);
				try {
					$this->autoprovisionIfPossible($this->session->get('user_saml.samlUserData'));
					$user = $this->userManager->get($this->userBackend->getCurrentUserId());
					if(!($user instanceof IUser)) {
						throw new NoUserFoundException('User' . $this->userBackend->getCurrentUserId() . ' not valid or not found');
					}
					$user->updateLastLoginTimestamp();
				} catch (NoUserFoundException $e) {
					if ($e->getMessage()) {
						$this->logger->warning('Error while trying to login using sso environment variable: ' . $e->getMessage(), ['app' => 'user_saml']);
					}
					$ssoUrl = $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned');
				}
				break;
			default:
				throw new \Exception(
					sprintf(
						'Type of "%s" is not supported for user_saml',
						$type
					)
				);
		}

		return new Http\RedirectResponse($ssoUrl);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param int $idp
	 * @return Http\DataDownloadResponse
	 * @throws Error
	 */
	public function getMetadata($idp) {
		$settings = new Settings($this->SAMLSettings->getOneLoginSettingsArray($idp));
		$metadata = $settings->getSPMetadata();
		$errors = $settings->validateMetadata($metadata);
		if (empty($errors)) {
			return new Http\DataDownloadResponse($metadata, 'metadata.xml', 'text/xml');
		} else {
			throw new Error(
				'Invalid SP metadata: '.implode(', ', $errors),
				Error::METADATA_SP_INVALID
			);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 * @OnlyUnauthenticatedUsers
	 * @NoSameSiteCookieRequired
	 *
	 * @return Http\RedirectResponse|void
	 * @throws Error
	 * @throws ValidationError
	 */
	public function assertionConsumerService() {
		$AuthNRequestID = $this->session->get('user_saml.AuthNRequestID');
		$idp = $this->session->get('user_saml.Idp');
		if(is_null($AuthNRequestID) || $AuthNRequestID === '' || is_null($idp)) {
			return;
		}

		$auth = new Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
		$auth->processResponse($AuthNRequestID);

		$this->logger->debug('Attributes send by the IDP: ' . json_encode($auth->getAttributes()));

		$errors = $auth->getErrors();

		if (!empty($errors)) {
			foreach($errors as $error) {
				$this->logger->error($error, ['app' => $this->appName]);
			}
			$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
		}

		if (!$auth->isAuthenticated()) {
			$this->logger->info('Auth failed', ['app' => $this->appName]);
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		// Check whether the user actually exists, if not redirect to an error page
		// explaining the issue.
		try {
			$this->autoprovisionIfPossible($auth->getAttributes());
		} catch (NoUserFoundException $e) {
			$this->logger->error($e->getMessage(), ['app' => $this->appName]);
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());
		try {
			$user = $this->userManager->get($this->userBackend->getCurrentUserId());
			if (!($user instanceof IUser)) {
				throw new \InvalidArgumentException('User is not valid');
			}
			$firstLogin = $user->updateLastLoginTimestamp();
			if($firstLogin) {
				$this->userBackend->initializeHomeDir($user->getUID());
			}
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => $this->appName]);
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		$originalUrl = $this->session->get('user_saml.OriginalUrl');
		if($originalUrl !== null && $originalUrl !== '') {
			$response = new Http\RedirectResponse($originalUrl);
		} else {
			$response = new Http\RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
		}
		$this->session->remove('user_saml.OriginalUrl');
		// The Nextcloud desktop client expects a cookie with the key of "_shibsession"
		// to be there.
		if($this->request->isUserAgent(['/^.*(mirall|csyncoC)\/.*$/'])) {
			$response->addCookie('_shibsession_', 'authenticated');
		}
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Http\RedirectResponse
	 * @throws Error
	 */
	public function singleLogoutService() {
		$isFromGS = ($this->config->getSystemValue('gs.enabled', false) &&
					 $this->config->getSystemValue('gss.mode', '') === 'master');

		// Some IDPs send the SLO request via POST, but OneLogin php-saml only handles GET.
		// To hack around this issue we copy the request from _POST to _GET.
		if(!empty($_POST['SAMLRequest'])) {
			$_GET['SAMLRequest'] = $_POST['SAMLRequest'];
		}

		$isFromIDP = !$isFromGS && !empty($_GET['SAMLRequest']);

		if($isFromIDP) {
			// requests comes from the IDP so let it manage the logout
			// (or raise Error if request is invalid)
			$pass = True ;
		} elseif($isFromGS) {
			// Request is from master GlobalScale
			// Request validity is check via a JSON Web Token
			$jwt = $this->request->getParam('jwt', '');
			$pass = $this->isValidJwt($jwt);
		} else {
			// standard request : need read CRSF check
			$pass = $this->request->passesCSRFCheck();
		}

		if($pass) {
			$idp = $this->session->get('user_saml.Idp');
			$auth = new Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
			$stay = true ; // $auth will return the redirect URL but won't perform the redirect himself
			if($isFromIDP){
				$keepLocalSession = true ; // do not let processSLO to delete the entire session. Let userSession->logout do the job
				$targetUrl = $auth->processSLO($keepLocalSession, null, false, null, $stay);
			} else {
				// If request is not from IDP, we must send him the logout request
				$parameters = array();
				$nameId = $this->session->get('user_saml.samlNameId');
				$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
				$targetUrl = $auth->logout(null, [], $nameId, $sessionIndex, $stay);
			}
			if(!empty($targetUrl) && !$auth->getLastErrorReason()){
				$this->userSession->logout();
			}
		}
		if(empty($targetUrl)){
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
			$message = $this->l->t('Unknown error, please check the log file for more details.');
		}
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 * @param string $redirectUrl
	 * @return Http\TemplateResponse
	 */
	public function selectUserBackEnd($redirectUrl) {

		$attributes = ['loginUrls' => []];

		if ($this->SAMLSettings->allowMultipleUserBackEnds()) {
			$attributes['loginUrls']['directLogin'] = [
				'url' => $this->getDirectLoginUrl($redirectUrl),
				'display-name' => $this->l->t('Direct log in')
			];
		}

		$attributes['loginUrls']['ssoLogin'] = $this->getIdps($redirectUrl);

		$attributes['useCombobox'] = count($attributes['loginUrls']['ssoLogin']) > 4 ? true : false;


		return new Http\TemplateResponse($this->appName, 'selectUserBackEnd', $attributes, 'guest');
	}

	/**
	 * get the IdPs showed at the login page
	 *
	 * @param $redirectUrl
	 * @return array
	 */
	private function getIdps($redirectUrl) {
		$result = [];
		$idps = $this->SAMLSettings->getListOfIdps();
		foreach ($idps as $idpId => $displayName) {
			$result[] = [
				'url' => $this->getSSOUrl($redirectUrl, $idpId),
				'display-name' => $this->getSSODisplayName($displayName),
			];
		}

		return $result;
	}

	/**
	 * get SSO URL
	 *
	 * @param $redirectUrl
	 * @param idp identifier
	 * @return string
	 */
	private function getSSOUrl($redirectUrl, $idp) {

		$originalUrl = '';
		if(!empty($redirectUrl)) {
			$originalUrl = $this->urlGenerator->getAbsoluteURL($redirectUrl);
		}


		$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
		$ssoUrl = $this->urlGenerator->linkToRouteAbsolute(
			'user_saml.SAML.login',
			[
				'requesttoken' => $csrfToken->getEncryptedValue(),
				'originalUrl' => $originalUrl,
				'idp' => $idp
			]
		);

		return $ssoUrl;

	}

	/**
	 * return the display name of the SSO identity provider
	 *
	 * @param $displayName
	 * @return string
	 */
	protected function getSSODisplayName($displayName) {
		if (empty($displayName)) {
			$displayName = $this->l->t('SSO & SAML log in');
		}

		return $displayName;
	}

	/**
	 * get Nextcloud login URL
	 *
	 * @return string
	 */
	private function getDirectLoginUrl($redirectUrl) {
		$directUrl = $this->urlGenerator->linkToRouteAbsolute('core.login.tryLogin', [
			'direct' => '1',
			'redirect_url' => $redirectUrl,
		]);
		return $directUrl;
	}

	private function isValidJwt($jwt) {
		try {
			$key = $this->config->getSystemValue('gss.jwt.key', '');
			JWT::decode($jwt, $key, ['HS256']);
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
		$message = $this->l->t('This page should not be visited directly.');
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}

}
