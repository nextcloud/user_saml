<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OCA\DAV\Connector\Sabre\Auth;
use OCA\User_SAML\Service\SessionService;
use OCP\IAppConfig;
use OCP\ISession;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class DavPlugin extends ServerPlugin {
	/** @noinspection PhpPropertyOnlyWrittenInspection */
	private Server $server;

	public function __construct(
		private readonly ISession $session,
		private readonly IAppConfig $config,
		private readonly array $auth,
		private readonly SAMLSettings $samlSettings,
		private readonly SessionService $sessionService,
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$server->on('beforeMethod:*', $this->beforeMethod(...), 9);
		$this->server = $server;
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response): void {
		if (
			$this->config->getValueString('user_saml', 'type', 'unset') === 'environment-variable'
			&& !$this->session->exists('user_saml.samlUserData')
		) {
			$uidMapping = $this->samlSettings->get(1)['general-uid_mapping'];
			if (isset($this->auth[$uidMapping])) {
				$this->session->set(Auth::DAV_AUTHENTICATED, $this->auth[$uidMapping]);
				$this->sessionService->prepareEnvironmentBasedSession($this->auth);
			}
		}
	}
}
