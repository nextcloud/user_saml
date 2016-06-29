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

use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;

class SAMLController extends Controller {
	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;
	/** @var SAMLSettings */
	private $SAMLSettings;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param SAMLSettings $SAMLSettings
	 */
	public function __construct($appName,
								IRequest $request,
								ISession $session,
								IUserSession $userSession,
								SAMLSettings $SAMLSettings) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->userSession = $userSession;
		$this->SAMLSettings = $SAMLSettings;
	}

	/**
	 * @PublicPage
	 * @UseSession
	 */
	public function login() {
		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$auth->login(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
		$ssoUrl = $auth->login(null, array(), false, false, true);
		$this->session->set('user_saml.AuthNRequestID', $auth->getLastRequestID());
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
	 */
	public function assertionConsumerService() {
		$AuthNRequestID = $this->session->get('AuthNRequestID');
		if(is_null($AuthNRequestID) || $AuthNRequestID === '') {
			return;
		}

		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$auth->processResponse($AuthNRequestID);

		$errors = $auth->getErrors();

		// FIXME: Appframworkize
		if (!empty($errors)) {
			print_r('<p>'.implode(', ', $errors).'</p>');
		}
		if (!$auth->isAuthenticated()) {
			echo "<p>Not authenticated</p>";
			exit();
		}

		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());

		return new Http\RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
	}

	/**
	 * @PublicPage
	 */
	public function singleLogoutService() {
		$auth = new \OneLogin_Saml2_Auth($this->SAMLSettings->getOneLoginSettingsArray());
		$returnTo = null;
		$parameters = array();
		$nameId = $this->session->get('user_saml.samlNameId');
		$sessionIndex = $this->session->get('user_saml.samlSessionIndex');
		$this->userSession->logout();
		$auth->logout($returnTo, $parameters, $nameId, $sessionIndex);
	}
}
