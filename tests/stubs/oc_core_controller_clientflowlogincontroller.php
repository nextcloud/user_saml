<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;

abstract class ClientFlowLoginController extends Controller {
	public const STATE_NAME = 'client.flow.state.token';
}
