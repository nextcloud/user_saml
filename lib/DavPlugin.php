<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OCA\DAV\Connector\Sabre\Auth;
use OCP\IConfig;
use OCP\ISession;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class DavPlugin extends ServerPlugin {
	private $session;
	private $config;
	private $auth;
	/** @var Server */
	private $server;
	/** @var SAMLSettings */
	private $samlSettings;

	public function __construct(ISession $session, IConfig $config, array $auth, SAMLSettings $samlSettings) {
		$this->session = $session;
		$this->config = $config;
		$this->auth = $auth;
		$this->samlSettings = $samlSettings;
	}


	public function initialize(Server $server) {
		// before auth
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 9);
		$this->server = $server;
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		if (
			$this->config->getAppValue('user_saml', 'type') === 'environment-variable' &&
			!$this->session->exists('user_saml.samlUserData')
		) {
			$uidMapping = $this->samlSettings->get(1)['general-uid_mapping'];
			if (isset($this->auth[$uidMapping])) {
				$this->session->set(Auth::DAV_AUTHENTICATED, $this->auth[$uidMapping]);
				$this->session->set('user_saml.samlUserData', $this->auth);
			}
		}
	}
}
