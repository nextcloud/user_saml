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

require_once __DIR__ . '/../3rdparty/vendor/autoload.php';

// If we run in CLI mode do not setup the app as it can fail the OCC execution
// since the URLGenerator isn't accessible.
$cli = false;
if(OC::$CLI) {
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
	\OC::$server->getLogger()->logException($e);
	return;
}
$samlSettings = new \OCA\User_SAML\SAMLSettings(
	$urlGenerator,
	$config,
	$request,
	$session
);

$userBackend = new \OCA\User_SAML\UserBackend(
	$config,
	$urlGenerator,
	\OC::$server->getSession(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserManager(),
	\OC::$server->getGroupManager(),
	$samlSettings,
	\OC::$server->getLogger()
);
$userBackend->registerBackends(\OC::$server->getUserManager()->getBackends());
OC_User::useBackend($userBackend);

$params = [];

// Setting up the one login config may fail, if so, do not catch the requests later.
$returnScript = false;
$type = '';
switch($config->getAppValue('user_saml', 'type')) {
	case 'saml':
		try {
			$oneLoginSettings = new \OneLogin\Saml2\Settings($samlSettings->getOneLoginSettingsArray(1));
		} catch (\OneLogin\SAML2\Error $e) {
			$returnScript = true;
		}
		$type = 'saml';
		break;
	case 'environment-variable':
		$type = 'environment-variable';
		break;
	default:
		return;
}

if ($type === 'environment-variable') {
	OC_User::handleApacheAuth();
}

if($returnScript === true) {
	return;
}

$app = new \OCA\User_SAML\AppInfo\Application();
$app->registerDavAuth();

$redirectSituation = false;

$user = $userSession->getUser();
if ($user !== null) {
	$enabled = $user->isEnabled();
	if ($enabled === false) {
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
if(!$cli &&
	!$userSession->isLoggedIn() &&
	\OC::$server->getRequest()->getPathInfo() === '/login' &&
	$type !== '') {
	try {
		$params = $request->getParams();
	} catch (\LogicException $e) {
		// ignore exception when PUT is called since getParams cannot parse parameters in that case
	}
	if (isset($params['direct'])) {
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
if($useSamlForDesktopClients === '1') {
	$currentUrl = substr(explode('?',$request->getRequestUri(), 2)[0], strlen(\OC::$WEBROOT));
	if(substr($currentUrl, 0, 12) === '/remote.php/' || substr($currentUrl, 0, 5) === '/ocs/') {
		if(!$userSession->isLoggedIn() && $request->isUserAgent([\OCP\IRequest::USER_AGENT_CLIENT_DESKTOP])) {
			$redirectSituation = true;

			if (preg_match('/^.*\/(\d+\.\d+\.\d+).*$/', $request->getHeader('USER_AGENT'), $matches) === 1) {
				$versionstring = $matches[1];

				if (version_compare($versionstring, '2.5.0', '>=') === true) {
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
	if(isset($params['redirect_url'])) {
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

if($redirectSituation === true) {
	try {
		$params = $request->getParams();
	} catch (\LogicException $e) {
		// ignore exception when PUT is called since getParams cannot parse parameters in that case
	}
	$originalUrl = '';
	if(isset($params['redirect_url'])) {
		$originalUrl = $urlGenerator->getAbsoluteURL($params['redirect_url']);
	}

	$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
	$targetUrl = $urlGenerator->linkToRouteAbsolute(
		'user_saml.SAML.login',
		[
			'requesttoken' => $csrfToken->getEncryptedValue(),
			'originalUrl' => $originalUrl,
			'idp' => 1,
		]
	);
	header('Location: '.$targetUrl);
	exit();
}
