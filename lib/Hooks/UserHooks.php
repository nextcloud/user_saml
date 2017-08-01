<?php
/**
 * @copyright Copyright (c) 2017 Benjamin Renard <brenard@easter-eggs.com>
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

namespace OCA\User_SAML\Hooks;
use OCP\IUserSession;

class UserHooks {

    private $userSession;

    public function __construct(IUserSession $userSession){
        $this->userSession = $userSession;
    }

    public function register() {
        $this->userSession->listen('\OC\User', 'logout', array($this, 'onLogout'));
    }

    public function onLogout() {
        $config = \OC::$server->getConfig();
        $urlGenerator = \OC::$server->getURLGenerator();
        $samlSettings = new \OCA\User_SAML\SAMLSettings(
            $urlGenerator,
            $config
        );
        if ($config->getAppValue('user_saml', 'type')==='saml' && $this->userSession->getSession()->exists('user_saml.samlNameId')) {
            $csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
            header('Location: '.$urlGenerator->linkToRouteAbsolute('user_saml.SAML.logout') .'?requesttoken='. urlencode($csrfToken->getEncryptedValue()));
            exit();
        }
        return True;
    }

}

