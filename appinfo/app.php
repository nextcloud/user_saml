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
$samlSettings = new \OCA\User_SAML\SAMLSettings(
	$urlGenerator,
	$config
);
try {
	$oneLoginSettings = new \OneLogin_Saml2_Settings($samlSettings->getOneLoginSettingsArray());
} catch(OneLogin_Saml2_Error $e) {
	return;
}

$userBackend = new \OCA\User_SAML\UserBackend(
	\OC::$server->getConfig(),
	\OC::$server->getLogger(),
	\OC::$server->getURLGenerator(),
	\OC::$server->getSession()
);
OC_User::useBackend($userBackend);
OC_User::handleApacheAuth();

// Redirect all requests to the login page to the SAML login
$currentUrl = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
if($currentUrl === '/server/index.php/login' && !OC_User::isLoggedIn()) {
	$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
	header('Location: '.$urlGenerator->linkToRouteAbsolute('user_saml.SAML.login') .'?requesttoken='. urlencode($csrfToken->getEncryptedValue()));
	exit();
}

