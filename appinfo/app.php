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

\OCP\App::registerAdmin('user_saml', 'admin');

$urlGenerator = \OC::$server->getURLGenerator();
$config = \OC::$server->getConfig();
$request = \OC::$server->getRequest();
$samlSettings = new \OCA\User_SAML\SAMLSettings(
	$urlGenerator,
	$config
);

$userBackend = new \OCA\User_SAML\UserBackend(
	\OC::$server->getConfig(),
	\OC::$server->getURLGenerator(),
	\OC::$server->getSession(),
	\OC::$server->getDb()
);
$userBackend->registerBackends(\OC::$server->getUserManager()->getBackends());
OC_User::useBackend($userBackend);
OC_User::handleApacheAuth();

// Setting up the one login config may fail, if so, do not catch the requests later.
try {
	$oneLoginSettings = new \OneLogin_Saml2_Settings($samlSettings->getOneLoginSettingsArray());
} catch(OneLogin_Saml2_Error $e) {
	return;
}

// Since with Nextcloud 9 we don't have an unique entry point this is a little
// bit hacky and won't necessarily detect all situations. So we inject some magic
// Javascript that does the work for us.
if(!OC_User::isLoggedIn()) {
	\OCP\Util::addHeader('script', ['src' => $urlGenerator->linkTo('user_saml', 'js/preauth.js')], '');
}

// If a request to OCS or remote.php is sent by the official desktop clients it can
// be intercepted as it supports SAML. All other clients don't yet and thus we
// require the usage of application specific passwords there.
$currentUrl = substr(explode('?',$request->getRequestUri(), 2)[0], strlen(\OC::$WEBROOT));
if(substr($currentUrl, 0, 12) === '/remote.php/' || substr($currentUrl, 0, 5) === '/ocs/') {
	if(!OC_User::isLoggedIn() && $request->isUserAgent([\OC\AppFramework\Http\Request::USER_AGENT_OWNCLOUD_DESKTOP])) {
		$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
		header('Location: '.$urlGenerator->linkToRouteAbsolute('user_saml.SAML.login') .'?requesttoken='. urlencode($csrfToken->getEncryptedValue()));
		exit();
	}
}
