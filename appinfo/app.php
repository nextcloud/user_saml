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

$urlGenerator = \OC::$server->getURLGenerator();
$request = \OC::$server->getRequest();
$userSession = \OC::$server->getUserSession();

$userBackend = new \OCA\User_OIDC\UserBackend(
	\OC::$server->getConfig(),
	$urlGenerator,
	\OC::$server->getSession(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserManager(),
	\OC::$server->getGroupManager(),
	\OC::$server->getLogger()
);
OC_User::useBackend($userBackend);

$app = new \OCA\User_OIDC\AppInfo\Application();
$app->registerDavAuth();

$redirectSituation = false;

$user = $userSession->getUser();
if ($user !== null) {
	$enabled = $user->isEnabled();
	if ($enabled === false) {
		$targetUrl = $urlGenerator->linkToRouteAbsolute(
			'user_oidc.OIDC.genericError',
			[
				'message' => 'This user account is disabled, please contact your administrator.'
			]
		);
		header('Location: '.$targetUrl);
		exit();
	}
}

// All requests that are not authenticated and match against the "/login" route are
// redirected to the OIDC login endpoint
if(!$cli &&
	!$userSession->isLoggedIn() &&
	\OC::$server->getRequest()->getPathInfo() === '/login') {
	$params = $request->getParams();
	if (isset($params['direct'])) {
		return;
	}
	$redirectSituation = true;
}

if($redirectSituation === true) {
	$params = $request->getParams();
	$originalUrl = '';
	if(isset($params['redirect_url'])) {
		$originalUrl = $urlGenerator->getAbsoluteURL($params['redirect_url']);
	}

	$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
	$targetUrl = $urlGenerator->linkToRouteAbsolute(
		'user_oidc.OIDC.login',
		[
			'requesttoken' => $csrfToken->getEncryptedValue(),
			'originalUrl' => $originalUrl,
		]
	);
	header('Location: '.$targetUrl);
	exit();
}
