<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Controller;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OC\Core\Controller\ClientFlowLoginController;
use OC\Core\Controller\ClientFlowLoginV2Controller;
use OC\Security\CSRF\CsrfTokenManager;
use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\Exceptions\UserFilterViolationException;
use OCA\User_SAML\Helper\TXmlHelper;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Security\ITrustedDomainHelper;
use OCP\Server;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\ValidationError;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class SAMLController extends Controller {
	use TXmlHelper;

	public function __construct(
		string $appName,
		IRequest $request,
		private ISession $session,
		private IUserSession $userSession,
		private SAMLSettings $samlSettings,
		private UserBackend $userBackend,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private IL10N $l,
		private UserResolver $userResolver,
		private UserData $userData,
		private ICrypto $crypto,
		private ITrustedDomainHelper $trustedDomainHelper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws NoUserFoundException
	 * @throws UserFilterViolationException
	 */
	private function autoprovisionIfPossible(): void {
		$auth = $this->userData->getAttributes();

		if (!$this->userData->hasUidMappingAttribute()) {
			throw new NoUserFoundException('IDP parameter for the UID not found. Possible parameters are: ' . json_encode(array_keys($auth)));
		}

		$this->assertGroupMemberships();

		if ($this->userData->getOriginalUid() === '') {
			$this->logger->error('Given UID is not valid, please check your attribute mapping', ['app' => $this->appName]);
			throw new \InvalidArgumentException('No valid UID given, please check your attribute mapping.');
		}
		$uid = $this->userData->getEffectiveUid();
		$userExists = $uid !== '';

		// if this server acts as a global scale master and the user is not
		// a local admin of the server we just create the user and continue
		// no need to update additional attributes
		$isGsEnabled = $this->config->getSystemValueBool('gs.enabled', false);
		$isGsMaster = $this->config->getSystemValueString('gss.mode', 'slave') === 'master';
		$isGsMasterAdmin = in_array($uid, $this->config->getSystemValue('gss.master.admin', []));
		if ($isGsEnabled && $isGsMaster && !$isGsMasterAdmin) {
			$this->userBackend->createUserIfNotExists($this->userData->getOriginalUid());
			return;
		}
		$autoProvisioningAllowed = $this->userBackend->autoprovisionAllowed();
		if ($userExists) {
			if ($autoProvisioningAllowed) {
				$this->userBackend->updateAttributes($uid);
			}
			return;
		}
		$uid = $this->userData->getOriginalUid();
		$uid = $this->userData->testEncodedObjectGUID($uid);
		if (!$userExists && !$autoProvisioningAllowed) {
			throw new NoUserFoundException('Auto provisioning not allowed and user ' . $uid . ' does not exist');
		} elseif (!$userExists && $autoProvisioningAllowed) {
			$this->userBackend->createUserIfNotExists($uid, $auth);
			$this->userBackend->updateAttributes($uid);
			return;
		}
	}

	/**
	 * @throws UserFilterViolationException
	 */
	protected function assertGroupMemberships(): void {
		if (!$this->userBackend->autoprovisionAllowed()) {
			// return early, when users are provided by a different backend
			// - mappings are not available/configurable in that case
			// - control is solely based on presence and enabled-state of the user
			return;
		}

		$groups = $this->userData->getGroups();
		$settings = $this->samlSettings->get($this->session->get('user_saml.Idp') ?? 1);

		$rejectGroupsString = $settings['saml-user-filter-reject_groups'] ?? '';
		$rejectGroups = array_map('trim', explode(',', $rejectGroupsString));

		if (!empty(array_intersect($groups, $rejectGroups))) {
			throw new UserFilterViolationException('User is member of a rejection group.');
		}

		$requireGroupsString = trim($settings['saml-user-filter-require_groups'] ?? '');
		$requireGroups = array_map('trim', explode(',', $requireGroupsString));
		if (!empty($requireGroupsString) && empty(array_intersect($groups, $requireGroups))) {
			throw new UserFilterViolationException('User is not member of a required group.');
		}
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @OnlyUnauthenticatedUsers
	 * @NoCSRFRequired
	 *
	 * @throws Exception
	 */
	public function login(int $idp = 1): Http\RedirectResponse|Http\TemplateResponse {
		$originalUrl = (string)$this->request->getParam('originalUrl', '');
		if (!$this->trustedDomainHelper->isTrustedUrl($originalUrl)) {
			$originalUrl = '';
		}

		$type = $this->config->getAppValue($this->appName, 'type');
		switch ($type) {
			case 'saml':
				$settings = $this->samlSettings->getOneLoginSettingsArray($idp);
				$auth = new Auth($settings);
				$passthroughParamsString = trim($settings['idp']['passthroughParameters'] ?? '') ;
				$passthroughParams = array_map('trim', explode(',', $passthroughParamsString));

				$passthroughValues = [];
				foreach ($passthroughParams as $passthroughParam) {
					$value = (string)$this->request->getParam($passthroughParam, '');
					if ($value !== '') {
						$passthroughValues[$passthroughParam] = $value;
					}
				}


				$returnUrl = $originalUrl ?: $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.login');
				$ssoUrl = $auth->login($returnUrl, $passthroughValues, false, false, true);
				$settings = $this->samlSettings->get($idp);
				$isSAMLRequestUsingPost = isset($settings['general-is_saml_request_using_post']) && $settings['general-is_saml_request_using_post'] === '1';

				if ($isSAMLRequestUsingPost) {
					$query = parse_url($ssoUrl, PHP_URL_QUERY);
					parse_str($query, $params);

					$samlRequest = $params['SAMLRequest'];
					$relayState = $params['RelayState'] ?? '';
					$sigAlg = $params['SigAlg'] ?? '';
					$signature = $params['Signature'] ?? '';
					$ssoUrl = explode('?', $ssoUrl)[0];

					$nonce = base64_encode(random_bytes(16));

					$response = new Http\TemplateResponse($this->appName, 'login_post', [
						'ssoUrl' => $ssoUrl,
						'samlRequest' => $samlRequest,
						'relayState' => $relayState,
						'sigAlg' => $sigAlg,
						'signature' => $signature,
						'nonce' => $nonce,
					], 'guest');

					$response->addHeader('Content-Security-Policy', "script-src 'self' 'nonce-$nonce' 'strict-dynamic' 'unsafe-eval';");
				} else {
					$response = new Http\RedirectResponse($ssoUrl);
				}

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
					'OriginalUrl' => $originalUrl,
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
				$ssoUrl = $originalUrl;
				if (empty($ssoUrl)) {
					$ssoUrl = $this->urlGenerator->getAbsoluteURL('/');
				}
				$this->session->set('user_saml.samlUserData', $_SERVER);
				try {
					$this->userData->setAttributes($this->session->get('user_saml.samlUserData'));
					$this->autoprovisionIfPossible();
					$user = $this->userResolver->findExistingUser($this->userBackend->getCurrentUserId());
					$firstLogin = $user->updateLastLoginTimestamp();
					if ($firstLogin) {
						$this->userBackend->initializeHomeDir($user->getUID());
					}
				} catch (NoUserFoundException $e) {
					if ($e->getMessage()) {
						$this->logger->warning('Error while trying to login using sso environment variable: ' . $e->getMessage(), ['app' => 'user_saml']);
					}
					$ssoUrl = $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned');
				} catch (UserFilterViolationException $e) {
					$this->logger->info(
						'SAML filter constraints not met: {msg}',
						[
							'app' => 'user_saml',
							'msg' => $e->getMessage(),
						]
					);
					$ssoUrl = $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notPermitted');
				}
				$response = new Http\RedirectResponse($ssoUrl);
				if (isset($e)) {
					$this->session->clear();
				}
				break;
			default:
				throw new Exception(
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
	 * @throws Error
	 */
	public function getMetadata(int $idp = 1): Http\DataDownloadResponse {
		$settings = new Settings($this->samlSettings->getOneLoginSettingsArray($idp));
		$metadata = $settings->getSPMetadata();
		$errors = $this->callWithXmlEntityLoader(fn () => $settings->validateMetadata($metadata));
		if (empty($errors)) {
			return new Http\DataDownloadResponse($metadata, 'metadata.xml', 'text/xml');
		} else {
			throw new Error(
				'Invalid SP metadata: ' . implode(', ', $errors),
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
		} catch (Exception) {
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
		// validator (called with processResponse()) needs an XML entity loader
		$this->callWithXmlEntityLoader(function () use ($auth, $AuthNRequestID): void {
			$auth->processResponse($AuthNRequestID);
		});

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
		$this->logger->debug('Authentication successful', ['app' => 'user_saml']);

		// Check whether the user actually exists, if not redirect to an error page
		// explaining the issue.
		try {
			$this->userData->setAttributes($auth->getAttributes());
			$this->autoprovisionIfPossible();
		} catch (NoUserFoundException $e) {
			$this->logger->error($e->getMessage(), ['app' => $this->appName, 'exception' => $e]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
			$response->invalidateCookie('saml_data');
			return $response;
		} catch (UserFilterViolationException $e) {
			$this->logger->error($e->getMessage(), ['app' => $this->appName, 'exception' => $e]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notPermitted'));
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
		$this->logger->debug('Session values set', ['app' => 'user_saml']);
		try {
			$user = $this->userResolver->findExistingUser($this->userBackend->getCurrentUserId());
			$firstLogin = $user->updateLastLoginTimestamp();
			if ($firstLogin) {
				$this->userBackend->initializeHomeDir($user->getUID());
			}
		} catch (NoUserFoundException) {
			throw new \InvalidArgumentException('User "' . $this->userBackend->getCurrentUserId() . '" is not valid');
		} catch (Exception $e) {
			$this->logger->critical($e->getMessage(), ['exception' => $e, 'app' => $this->appName]);
			$response = new Http\RedirectResponse($this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.notProvisioned'));
			$response->invalidateCookie('saml_data');
			return $response;
		}
		$this->logger->debug('User found, last login timestamp updated', ['app' => 'user_saml']);

		$originalUrl = $data['RelayState'] ?? $data['OriginalUrl'];
		if ($originalUrl !== null && $originalUrl !== '') {
			$response = new Http\RedirectResponse($originalUrl);
		} else {
			$response = new Http\RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
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
	 * @UseSession
	 * @throws Error
	 */
	public function singleLogoutService(): Http\RedirectResponse {
		$isFromGS = ($this->config->getSystemValueBool('gs.enabled', false)
					 && $this->config->getSystemValueString('gss.mode', '') === 'master');

		// Some IDPs send the SLO request via POST, but OneLogin php-saml only handles GET.
		// To hack around this issue we copy the request from _POST to _GET.
		if (!empty($_POST['SAMLRequest'])) {
			$_GET['SAMLRequest'] = $_POST['SAMLRequest'];
		}

		$isFromIDP = !$isFromGS && !empty($_GET['SAMLRequest']);
		$idp = null;
		if ($isFromIDP) {
			// requests comes from the IDP so let it manage the logout
			// (or raise Error if request is invalid)
			$pass = true;
		} elseif ($isFromGS) {
			// Request is from master GlobalScale
			$jwt = $this->request->getParam('jwt', '');

			try {
				$key = $this->config->getSystemValueString('gss.jwt.key', '');
				$decoded = (array)JWT::decode($jwt, new Key($key, 'HS256'));

				$idp = $decoded['idp'] ?? null;
				$pass = true;
			} catch (Exception) {
			}
		} else {
			// standard request : need read CRSF check
			$pass = $this->request->passesCSRFCheck();
		}

		if ($pass) {
			$idp = ($idp !== null) ? (int)$idp : $this->session->get('user_saml.Idp');
			$stay = true; // $auth will return the redirect URL but won't perform the redirect himself
			if ($isFromIDP) {
				[$targetUrl, $auth] = $this->tryProcessSLOResponse($idp);
				if ($auth !== null) {
					$errors = $auth->getErrors();
					if (!empty($errors)) {
						foreach ($errors as $error) {
							$this->logger->error($error, ['app' => $this->appName]);
						}
						$this->logger->error($auth->getLastErrorReason(), ['app' => $this->appName]);
					}
				} else {
					$this->logger->error('Error while handling SLO request: missing session data, and request is not satisfied by any configuration');
				}
			} else {
				// If request is not from IDP, we send the logout request to the IDP
				$auth = new Auth($this->samlSettings->getOneLoginSettingsArray($idp ?? 1));
				$nameId = $this->session->get('user_saml.samlNameId');
				$nameIdFormat = $this->session->get('user_saml.samlNameIdFormat');
				$nameIdNameQualifier = $this->session->get('user_saml.samlNameIdNameQualifier');
				$nameIdSPNameQualifier = $this->session->get('user_saml.samlNameIdSPNameQualifier');
				$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
				try {
					$targetUrl = $auth->logout(null, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier, $nameIdSPNameQualifier);
				} catch (Error $e) {
					$this->logger->warning($e->getMessage(), ['exception' => $e, 'app' => $this->appName]);
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
	 * @returns [?string, ?Auth]
	 */
	private function tryProcessSLOResponse(?int $idp): array {
		$idps = ($idp !== null) ? [$idp] : array_keys($this->samlSettings->getListOfIdps());
		foreach ($idps as $idp) {
			try {
				$auth = new Auth($this->samlSettings->getOneLoginSettingsArray($idp));
				// validator (called with processSLO()) needs an XML entity loader
				$targetUrl = $this->callWithXmlEntityLoader(fn (): string => $auth->processSLO(
					true, // do not let processSLO to delete the entire session. Let userSession->logout do the job
					null,
					$this->samlSettings->usesSloWebServerDecode($idp),
					null,
					true
				));
				if ($auth->getLastErrorReason() === null) {
					return [$targetUrl, $auth];
				}
			} catch (Error) {
				continue;
			}
		}
		return [null, null];
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function notProvisioned(): Http\TemplateResponse {
		return new Http\TemplateResponse($this->appName, 'notProvisioned', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function notPermitted(): Http\TemplateResponse {
		return new Http\TemplateResponse($this->appName, 'notPermitted', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function genericError(string $message): Http\TemplateResponse {
		if (empty($message)) {
			$message = $this->l->t('Unknown error, please check the log file for more details.');
		}
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @OnlyUnauthenticatedUsers
	 */
	public function selectUserBackEnd(string $redirectUrl = ''): Http\TemplateResponse {
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

		$attributes['useCombobox'] = count($attributes['loginUrls']['ssoLogin']) > 4;


		return new Http\TemplateResponse($this->appName, 'selectUserBackEnd', $attributes, 'guest');
	}

	/**
	 * get the IdPs showed at the login page
	 */
	private function getIdps(string $redirectUrl): array {
		$result = [];
		$idps = $this->samlSettings->getListOfIdps();
		foreach ($idps as $idpId => $displayName) {
			$result[] = [
				'url' => $this->getSSOUrl($redirectUrl, (string)$idpId),
				'display-name' => $this->getSSODisplayName($displayName),
			];
		}

		return $result;
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws \OCP\DB\Exception
	 */
	private function getSSOUrl(string $redirectUrl, string $idp): string {
		$originalUrl = '';
		if (!empty($redirectUrl)) {
			$originalUrl = $this->urlGenerator->getAbsoluteURL($redirectUrl);
		}

		/** @var CsrfTokenManager $csrfTokenManager */
		$csrfTokenManager = Server::get(CsrfTokenManager::class);
		$csrfToken = $csrfTokenManager->getToken();

		$settings = $this->samlSettings->get((int)$idp);
		$method = $settings['general-is_saml_request_using_post'] ?? 'get';

		return $this->urlGenerator->linkToRouteAbsolute(
			'user_saml.SAML.login',
			[
				'requesttoken' => $csrfToken->getEncryptedValue(),
				'originalUrl' => $originalUrl,
				'idp' => $idp,
				'method' => $method,
			]
		);
	}

	/**
	 * return the display name of the SSO identity provider
	 */
	protected function getSSODisplayName(?string $displayName): string {
		if (empty($displayName)) {
			$displayName = $this->l->t('SSO & SAML log in');
		}

		return $displayName;
	}

	/**
	 * get Nextcloud login URL
	 */
	private function getDirectLoginUrl(string $redirectUrl): string {
		$directUrl = $this->urlGenerator->linkToRouteAbsolute('core.login.tryLogin', [
			'direct' => '1',
			'redirect_url' => $redirectUrl,
		]);
		return $directUrl;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function base(): Http\TemplateResponse {
		$message = $this->l->t('This page should not be visited directly.');
		return new Http\TemplateResponse($this->appName, 'error', ['message' => $message], 'guest');
	}
}
