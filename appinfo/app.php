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

use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../3rdparty/vendor/autoload.php';

// If we run in CLI mode do not set up the app as it can fail the OCC execution
// since the URLGenerator isn't accessible.
$cli = false;
if (OC::$CLI) {
	$cli = true;
}
try {
	$urlGenerator = \OC::$server->getURLGenerator();
	$l = \OC::$server->getL10N('user_saml');
	$config = \OC::$server->getConfig();
	$request = \OC::$server->getRequest();
	$userSession = \OC::$server->getUserSession();
	$session = \OC::$server->getSession();
} catch (Throwable $e) {
	$logger = \OCP\Server::get(LoggerInterface::class);
	$logger->critical($e->getMessage(), ['exception' => $e, 'app' => 'user_saml']);
	return;
}

$groupBackend = \OC::$server->get(GroupBackend::class);
\OC::$server->get(IGroupManager::class)->addBackend($groupBackend);

$samlSettings = \OC::$server->get(SAMLSettings::class);

$userBackend = \OCP\Server::get(UserBackend::class);
$userBackend->registerBackends(\OC::$server->getUserManager()->getBackends());
OC_User::useBackend($userBackend);

$params = [];

// Setting up the one login config may fail, if so, do not catch the requests later.
$returnScript = false;
$type = '';
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
	if (substr($uri, -24) === '/apps/oauth/api/v1/token') {
		return;
	}

	OC_User::handleApacheAuth();
}

if ($returnScript === true) {
	return;
}

$app = \OC::$server->query(\OCA\User_SAML\AppInfo\Application::class);
$app->registerDavAuth();

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
				'message' => $l->t('This user account is disabled, please contact your administrator.')
			]
		);
		header('Location: '.$targetUrl);
		exit();
	}
}

// All requests that are not authenticated and match against the "/login" route are
// redirected to the SAML login endpoint
if (!$cli &&
	!$userSession->isLoggedIn() &&
	\OC::$server->getRequest()->getPathInfo() === '/login' &&
	$type !== '') {
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

$multipleUserBackEnds = $samlSettings->allowMultipleUserBackEnds();
$configuredIdps = $samlSettings->getListOfIdps();
$showLoginOptions = ($multipleUserBackEnds || count($configuredIdps) > 1) && $type === 'saml';

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
	header('Location: '.$targetUrl);
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

	$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
	$targetUrl = $urlGenerator->linkToRouteAbsolute(
		'user_saml.SAML.login',
		[
			'requesttoken' => $csrfToken->getEncryptedValue(),
			'originalUrl' => $originalUrl,
			'idp' => array_keys($configuredIdps)[0] ?? '',
		]
	);
	header('Location: '.$targetUrl);
	exit();
}
