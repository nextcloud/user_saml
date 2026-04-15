<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CleanupRemovedConfig implements IRepairStep {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Cleans up config keys that are not used anymore';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$this->appConfig->deleteAppValue('general-use_saml_auth_for_desktop');
	}
}
