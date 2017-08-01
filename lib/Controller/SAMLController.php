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

namespace OCA\User_SAML\Controller;

use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
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
								ILogger $logger) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->userSession = $userSession;
		$this->SAMLSettings = $SAMLSettings;
		$this->userBackend = $userBackend;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * @param array $auth
	 * @throws NoUserFoundException
	 */
	private function autoprovisionIfPossible(array $auth) {
		$uidMapping = $this->config->getAppValue('user_saml', 'general-uid_mapping');
		if(isset($auth[$uidMapping])) {
			if(is_array($auth[$uidMapping])) {
				$uid = $auth[$uidMapping][0];
			} else {
				$uid = $auth[$uidMapping];
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
	 * @return Http\RedirectResponse
	 * @throws \Exception
	 */
	public function login() {
		$type = $this->config->getAppValue($this->appName, 'type');
		switch($type) {
			case 'saml':
				$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
				$ssoUrl = $auth->login(null, [], false, false, true);
				$this->session->set('user_saml.AuthNRequestID', $auth->getLastRequestID());
				break;
			case 'environment-variable':
				$ssoUrl = $this->urlGenerator->getAbsoluteURL('/');
				$this->session->set('user_saml.samlUserData', $_SERVER);
				try {
					$this->autoprovisionIfPossible($this->session->get('user_saml.samlUserData'));
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
	 */
	public function getMetadata() {
		$settings = new \OneLogin_Saml2_Settings($this->SAMLSettings->getOneLoginSettingsArray());
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
	 *
	 * @return Http\RedirectResponse|void
	 */
	public function assertionConsumerService() {
		$AuthNRequestID = $this->session->get('user_saml.AuthNRequestID');
		if(is_null($AuthNRequestID) || $AuthNRequestID === '') {
			return;
		}

		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$auth->processResponse($AuthNRequestID);

		$errors = $auth->getErrors();

		if (!empty($errors)) {
			foreach($errors as $error) {
				$this->logger->error($error, ['app' => $this->appName]);
			}
			$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
		}

		if (!$auth->isAuthenticated()) {
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		// Check whether the user actually exists, if not redirect to an error page
		// explaining the issue.
		try {
			$this->autoprovisionIfPossible($auth->getAttributes());
		} catch (NoUserFoundException $e) {
			return new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
		}

		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlNameIdFormat', $auth->getNameIdFormat());
		$this->session->set('user_saml.samlNameIdNameQualifier', $auth->getNameIdNameQualifier());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());

		$response = new Http\RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
		// The Nextcloud desktop client expects a cookie with the key of "_shibsession"
		// to be there.
		if($this->request->isUserAgent(['/^.*(mirall|csyncoC)\/.*$/'])) {
			$response->addCookie('_shibsession_', 'authenticated');
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 * @return Http\RedirectResponse
	 */
	public function logout() {
		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
		$returnTo = \OC::$server->getURLGenerator()->linkToRouteAbsolute('core.login.logout').'?requesttoken='.urlencode($csrfToken->getEncryptedValue());
		$parameters = array();
		$nameId = $this->session->get('user_saml.samlNameId');
		$nameIdFormat = $this->session->get('user_saml.samlNameIdFormat');
		$nameIdNameQualifier = $this->session->get('user_saml.samlNameIdNameQualifier');
		$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
		$sloBuiltUrl = $auth->logout($returnTo, $parameters, $nameId, $sessionIndex, true, $nameIdFormat, $nameIdNameQualifier);
		$this->session->set('user_saml.LogoutRequestID', $auth->getLastRequestID());
		$response = new Http\RedirectResponse($sloBuiltUrl);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 * @NoCSRFRequired
	 * @return Http\RedirectResponse
	 */
	public function singleLogoutService() {
		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$LogoutRequestID = $this->session->get('user_saml.LogoutRequestID');
		if($LogoutRequestID === '') {
			$LogoutRequestID=null;
		}

		$destroySessionCallback = function() {
			$this->session->remove('user_saml.AuthNRequestID');
			$this->session->remove('user_saml.LogoutRequestID');
			$this->session->remove('user_saml.samlUserData');
			$this->session->remove('user_saml.samlNameId');
			$this->session->remove('user_saml.samlNameIdFormat');
			$this->session->remove('user_saml.samlNameIdNameQualifier');
			$this->session->remove('user_saml.samlSessionIndex');
			$this->session->remove('user_saml.samlSessionExpiration');
			return true;
		};

		$auth->processSLO(false, $LogoutRequestID, false, $destroySessionCallback);
		$errors = $auth->getErrors();
		if (!empty($errors)) {
			foreach($errors as $error) {
				$this->logger->error($error, ['app' => $this->appName]);
			}
			$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
		}


		$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
		$response = new Http\RedirectResponse(\OC::$server->getURLGenerator()->linkToRouteAbsolute('core.login.logout').'?requesttoken='.urlencode($csrfToken->getEncryptedValue()));
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function notProvisioned() {
		return new Http\TemplateResponse($this->appName, 'notProvisioned', [], 'guest');
	}
}
