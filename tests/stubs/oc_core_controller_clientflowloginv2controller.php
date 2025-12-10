<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;

class ClientFlowLoginV2Controller extends Controller {
	public const TOKEN_NAME = 'client.flow.v2.login.token';
	public const STATE_NAME = 'client.flow.v2.state.token';
	// Denotes that the session was created for the login flow and should therefore be ephemeral.
	public const EPHEMERAL_NAME = 'client.flow.v2.state.ephemeral';
}
