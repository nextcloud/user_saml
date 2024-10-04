<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CleanupRemovedConfig implements IRepairStep {

	public function __construct(
		protected IConfig $config,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'Cleans up config keys that are not used anymore';
	}

	/**
	 * @inheritDoc
	 */
	public function run(IOutput $output) {
		$this->config->deleteAppValue('user_saml', 'general-use_saml_auth_for_desktop');
	}
}
