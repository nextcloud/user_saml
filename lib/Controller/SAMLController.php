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
				throw new NoUserFoundException();
			} elseif(!$userExists && $autoProvisioningAllowed) {
				$this->userBackend->createUserIfNotExists($uid);
				$this->userBackend->updateAttributes($uid, $auth);
				return;
			}
		}

		throw new NoUserFoundException();
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @OnlyUnauthenticatedUsers
	 *
	 * @param int $idp id of the idp
	 * @return Http\RedirectResponse
	 * @throws \Exception
	 */
	public function login($idp) {
		$type = $this->config->getAppValue($this->appName, 'type');
		switch($type) {
			case 'saml':
				$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
				$ssoUrl = $auth->login(null, [], false, false, true);
				$this->session->set('user_saml.AuthNRequestID', $auth->getLastRequestID());
				$this->session->set('user_saml.OriginalUrl', $this->request->getParam('originalUrl', ''));
				$this->session->set('user_saml.Idp', $idp);
				break;
			case 'environment-variable':
				$ssoUrl = $this->urlGenerator->getAbsoluteURL('/');
				$this->session->set('user_saml.samlUserData', $_SERVER);
				try {
					$this->autoprovisionIfPossible($this->session->get('user_saml.samlUserData'));
					$user = $this->userManager->get($this->userBackend->getCurrentUserId());
					if(!($user instanceof IUser)) {
						throw new NoUserFoundException();
					}
					$user->updateLastLoginTimestamp();
				} catch (NoUserFoundException $e) {
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
	 * @throws \OneLogin_Saml2_Error
	 */
	public function getMetadata($idp) {
		$settings = new \OneLogin_Saml2_Settings($this->SAMLSettings->getOneLoginSettingsArray($idp));
		$metadata = $settings->getSPMetadata();
		$errors = $settings->validateMetadata($metadata);
		if (empty($errors)) {
			return new Http\DataDownloadResponse($metadata, 'metadata.xml', 'text/xml');
		} else {
			throw new \OneLogin_Saml2_Error(
				'Invalid SP metadata: '.implode(', ', $errors),
				\OneLogin_Saml2_Error::METADATA_SP_INVALID
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
	 */
	public function assertionConsumerService() {
		$AuthNRequestID = $this->session->get('user_saml.AuthNRequestID');
		$idp = $this->session->get('user_saml.Idp');
		if(is_null($AuthNRequestID) || $AuthNRequestID === '' || is_null($idp)) {
			return;
		}

		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
		$auth->processResponse($AuthNRequestID);

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
			$this->logger->info('User not found', ['app' => $this->appName]);
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());
		try {
			$user = $this->userManager->get($this->userBackend->getCurrentUserId());
			if(!($user instanceof IUser)) {
				throw new \InvalidArgumentException('User is not valid');
			}
			$user->updateLastLoginTimestamp();
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Http\RedirectResponse
	 */
	public function singleLogoutService() {
		if($this->request->passesCSRFCheck()) {
			$idp = $this->session->get('user_saml.Idp');
			$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray($idp));
			$returnTo = null;
			$parameters = array();
			$nameId = $this->session->get('user_saml.samlNameId');
			$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
			$this->userSession->logout();
			$targetUrl = $auth->logout($returnTo, $parameters, $nameId, $sessionIndex, true);
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

		$loginUrls = [];

		if ($this->SAMLSettings->allowMultipleUserBackEnds()) {
			$loginUrls['directLogin'] = [
				'url' => $this->getDirectLoginUrl($redirectUrl),
				'display-name' => $this->l->t('Direct log in')
			];
		}

		$loginUrls['ssoLogin'] = $this->getIdps($redirectUrl);

		return new Http\TemplateResponse($this->appName, 'selectUserBackEnd', $loginUrls, 'guest');
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

}
