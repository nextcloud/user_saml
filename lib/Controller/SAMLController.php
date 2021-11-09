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
use OC\Core\Controller\ClientFlowLoginController;
use OC\Core\Controller\ClientFlowLoginV2Controller;
use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\ICrypto;
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
	private $samlSettings;
	/** @var UserBackend */
	private $userBackend;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ILogger */
	private $logger;
	/** @var IL10N */
	private $l;
	/** @var UserResolver */
	private $userResolver;
	/** @var UserData */
	private $userData;
	/**
	 * @var ICrypto
	 */
	private $crypto;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param SAMLSettings $samlSettings
	 * @param UserBackend $userBackend
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param IL10N $l
	 */
	public function __construct(
					  $appName,
		IRequest      $request,
		ISession      $session,
		IUserSession  $userSession,
		SAMLSettings  $samlSettings,
		UserBackend   $userBackend,
		IConfig       $config,
		IURLGenerator $urlGenerator,
		ILogger       $logger,
		IL10N         $l,
		UserResolver  $userResolver,
		UserData      $userData,
		ICrypto       $crypto
	) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->userSession = $userSession;
		$this->samlSettings = $samlSettings;
		$this->userBackend = $userBackend;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->l = $l;
		$this->userResolver = $userResolver;
		$this->userData = $userData;
		$this->crypto = $crypto;
	}

	/**
	 * @throws NoUserFoundException
	 */
	private function autoprovisionIfPossible() {
		$auth = $this->userData->getAttributes();

		if (!$this->userData->hasUidMappingAttribute()) {
			throw new NoUserFoundException('IDP parameter for the UID not found. Possible parameters are: ' . json_encode(array_keys($auth)));
		}

		if ($this->userData->getOriginalUid() === '') {
			$this->logger->error('Uid is not a valid uid please check your attribute mapping', ['app' => $this->appName]);
			throw new \InvalidArgumentException('No valid uid given, please check your attribute mapping.');
		}
		$uid = $this->userData->getEffectiveUid();
		$userExists = $uid !== '';

		// if this server acts as a global scale master and the user is not
		// a local admin of the server we just create the user and continue
		// no need to update additional attributes
		$isGsEnabled = $this->config->getSystemValue('gs.enabled', false);
		$isGsMaster = $this->config->getSystemValue('gss.mode', 'slave') === 'master';
		$isGsMasterAdmin = in_array($uid, $this->config->getSystemValue('gss.master.admin', []));
		if ($isGsEnabled && $isGsMaster && !$isGsMasterAdmin) {
			$this->userBackend->createUserIfNotExists($this->userData->getOriginalUid());
			return;
		}
		$autoProvisioningAllowed = $this->userBackend->autoprovisionAllowed();
		if ($userExists) {
			if ($autoProvisioningAllowed) {
				$this->userBackend->updateAttributes($uid, $auth);
			}
			return;
		}
		$uid = $this->userData->getOriginalUid();
		$uid = $this->userData->testEncodedObjectGUID($uid);
		if (!$userExists && !$autoProvisioningAllowed) {
			throw new NoUserFoundException('Auto provisioning not allowed and user ' . $uid . ' does not exist');
		} elseif (!$userExists && $autoProvisioningAllowed) {
			$this->userBackend->createUserIfNotExists($uid, $auth);
			$this->userBackend->updateAttributes($uid, $auth);
			return;
		}
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
		switch ($type) {
			case 'saml':
				$auth = new Auth($this->samlSettings->getOneLoginSettingsArray($idp));
				$ssoUrl = $auth->login(null, [], false, false, true);
				$response = new Http\RedirectResponse($ssoUrl);

				// Small hack to make user_saml work with the loginflows
				$flowData = [];

				if ($this->session->get(ClientFlowLoginController::STATE_NAME) !== null) {
					$flowData['cf1'] = $this->session->get(ClientFlowLoginController::STATE_NAME);
				} elseif ($this->session->get(ClientFlowLoginV2Controller::TOKEN_NAME) !== null) {
					$flowData['cf2'] = [
						'token' => $this->session->get(ClientFlowLoginV2Controller::TOKEN_NAME),
						'state' => $this->session->get(ClientFlowLoginV2Controller::STATE_NAME),
					];
				}

				// Pack data as JSON so we can properly extract it later
				$data = json_encode([
					'AuthNRequestID' => $auth->getLastRequestID(),
					'OriginalUrl' => $this->request->getParam('originalUrl', ''),
					'Idp' => $idp,
					'flow' => $flowData,
				]);

				// Encrypt it
				$data = $this->crypto->encrypt($data);

				// And base64 encode it
				$data = base64_encode($data);

				$response->addCookie('saml_data', $data, null, 'None');
				break;
			case 'environment-variable':
				$ssoUrl = $this->request->getParam('originalUrl', '');
				if (empty($ssoUrl)) {
					$ssoUrl = $this->urlGenerator->getAbsoluteURL('/');
				}
				$this->session->set('user_saml.samlUserData', $_SERVER);
				try {
					$this->userData->setAttributes($this->session->get('user_saml.samlUserData'));
					$this->autoprovisionIfPossible();
					$user = $this->userResolver->findExistingUser($this->userBackend->getCurrentUserId());
					$user->updateLastLoginTimestamp();
				} catch (NoUserFoundException $e) {
					if ($e->getMessage()) {
						$this->logger->warning('Error while trying to login using sso environment variable: ' . $e->getMessage(), ['app' => 'user_saml']);
					}
					$ssoUrl = $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned');
				}
				$response = new Http\RedirectResponse($ssoUrl);
				break;
			default:
				throw new \Exception(
					sprintf(
						'Type of "%s" is not supported for user_saml',
						$type
					)
				);
		}

		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param int $idp
	 * @return Http\DataDownloadResponse
	 * @throws Error
	 */
	public function getMetadata($idp) {
		$settings = new Settings($this->samlSettings->getOneLoginSettingsArray($idp));
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
	 * @return Http\RedirectResponse
	 * @throws Error
	 * @throws ValidationError
	 */
	public function assertionConsumerService(): Http\RedirectResponse {
		// Fetch and decrypt the cookie
		$cookie = $this->request->getCookie('saml_data');
		if ($cookie === null) {
			$this->logger->debug('Cookie was not present', ['app' => 'user_saml']);
			return new Http\RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
		}

		// Base64 decode
		$cookie = base64_decode($cookie);

		// Decrypt and deserialize
		try {
			$cookie = $this->crypto->decrypt($cookie);
		} catch (\Exception $e) {
			$this->logger->debug('Could not decrypt SAML cookie', ['app' => 'user_saml']);
			return new Http\RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
		}
		$data = json_decode($cookie, true);

		if (isset($data['flow'])) {
			if (isset($data['flow']['cf1'])) {
				$this->session->set(ClientFlowLoginController::STATE_NAME, $data['flow']['cf1']);
			} elseif (isset($data['flow']['cf2'])) {
				$this->session->set(ClientFlowLoginV2Controller::TOKEN_NAME, $data['flow']['cf2']['token']);
				$this->session->set(ClientFlowLoginV2Controller::STATE_NAME, $data['flow']['cf2']['state']);
			}
		}

		$AuthNRequestID = $data['AuthNRequestID'];
		$idp = $data['Idp'];
		// need to keep the IdP config ID during session lifetime (SAMLSettings::getPrefix)
		$this->session->set('user_saml.Idp', $idp);
		if (is_null($AuthNRequestID) || $AuthNRequestID === '' || is_null($idp)) {
			$this->logger->debug('Invalid auth payload', ['app' => 'user_saml']);
			return new Http\RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
		}

		$auth = new Auth($this->samlSettings->getOneLoginSettingsArray($idp));
		$auth->processResponse($AuthNRequestID);

		$this->logger->debug('Attributes send by the IDP: ' . json_encode($auth->getAttributes()));

		$errors = $auth->getErrors();

		if (!empty($errors)) {
			foreach ($errors as $error) {
				$this->logger->error($error, ['app' => $this->appName]);
			}
			$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
		}

		if (!$auth->isAuthenticated()) {
			$this->logger->info('Auth failed', ['app' => $this->appName]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
			$response->invalidateCookie('saml_data');
			return $response;
		}

		// Check whether the user actually exists, if not redirect to an error page
		// explaining the issue.
		try {
			$this->userData->setAttributes($auth->getAttributes());
			$this->autoprovisionIfPossible();
		} catch (NoUserFoundException $e) {
			$this->logger->error($e->getMessage(), ['app' => $this->appName]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
			$response->invalidateCookie('saml_data');
			return $response;
		}

		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlNameIdFormat', $auth->getNameIdFormat());
		$this->session->set('user_saml.samlNameIdNameQualifier', $auth->getNameIdNameQualifier());
		$this->session->set('user_saml.samlNameIdSPNameQualifier', $auth->getNameIdSPNameQualifier());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());
		try {
			$user = $this->userResolver->findExistingUser($this->userBackend->getCurrentUserId());
			$firstLogin = $user->updateLastLoginTimestamp();
			if ($firstLogin) {
				$this->userBackend->initializeHomeDir($user->getUID());
			}
		} catch (NoUserFoundException $e) {
			throw new \InvalidArgumentException('User "' . $this->userBackend->getCurrentUserId() . '" is not valid');
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => $this->appName]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
			$response->invalidateCookie('saml_data');
			return $response;
		}

		$originalUrl = $data['OriginalUrl'];
		if ($originalUrl !== null && $originalUrl !== '') {
			$response = new Http\RedirectResponse($originalUrl);
		} else {
			$response = new Http\RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
		}
		// The Nextcloud desktop client expects a cookie with the key of "_shibsession"
		// to be there.
		if ($this->request->isUserAgent(['/^.*(mirall|csyncoC)\/.*$/'])) {
			$response->addCookie('_shibsession_', 'authenticated');
		}

		$response->invalidateCookie('saml_data');
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
		if (!empty($_POST['SAMLRequest'])) {
			$_GET['SAMLRequest'] = $_POST['SAMLRequest'];
		}

		$isFromIDP = !$isFromGS && !empty($_GET['SAMLRequest']);

		if ($isFromIDP) {
			// requests comes from the IDP so let it manage the logout
			// (or raise Error if request is invalid)
			$pass = true ;
		} elseif ($isFromGS) {
			// Request is from master GlobalScale
			// Request validity is check via a JSON Web Token
			$jwt = $this->request->getParam('jwt', '');
			$pass = $this->isValidJwt($jwt);
		} else {
			// standard request : need read CRSF check
			$pass = $this->request->passesCSRFCheck();
		}

		if ($pass) {
			$idp = $this->session->get('user_saml.Idp');
			$auth = new Auth($this->samlSettings->getOneLoginSettingsArray($idp));
			$stay = true ; // $auth will return the redirect URL but won't perform the redirect himself
			if ($isFromIDP) {
				$keepLocalSession = true ; // do not let processSLO to delete the entire session. Let userSession->logout do the job
				$targetUrl = $auth->processSLO(
					$keepLocalSession,
					null,
					$this->samlSettings->usesSloWebServerDecode(),
					null,
					$stay
				);

				$errors = $auth->getErrors();
				if (!empty($errors)) {
					foreach ($errors as $error) {
						$this->logger->error($error, ['app' => $this->appName]);
					}
					$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
				}
			} else {
				// If request is not from IDP, we send the logout request to the IDP
				$parameters = [];
				$nameId = $this->session->get('user_saml.samlNameId');
				$nameIdFormat = $this->session->get('user_saml.samlNameIdFormat');
				$nameIdNameQualifier = $this->session->get('user_saml.samlNameIdNameQualifier');
				$nameIdSPNameQualifier = $this->session->get('user_saml.samlNameIdSPNameQualifier');
				$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
				try {
					$targetUrl = $auth->logout(null, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier, $nameIdSPNameQualifier);
				} catch (Error $e) {
					$this->logger->logException($e, ['level' => ILogger::WARN]);
					$this->userSession->logout();
				}
			}
			if (!empty($targetUrl) && !$auth->getLastErrorReason()) {
				$this->userSession->logout();
			}
		}
		if (empty($targetUrl)) {
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

		if ($this->samlSettings->allowMultipleUserBackEnds()) {
			$displayName = $this->l->t('Direct log in');

			$customDisplayName = $this->config->getAppValue('user_saml', 'directLoginName', '');
			if ($customDisplayName !== '') {
				$displayName = $customDisplayName;
			}

			$attributes['loginUrls']['directLogin'] = [
				'url' => $this->getDirectLoginUrl($redirectUrl),
				'display-name' => $displayName,
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
		$idps = $this->samlSettings->getListOfIdps();
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
		if (!empty($redirectUrl)) {
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
