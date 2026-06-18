<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Services\IAppConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigValidate extends Base {

	private const REQUIRED_FIELDS = [
		'idp-entityId',
		'idp-singleSignOnService.url',
		'general-uid_mapping',
	];

	public function __construct(
		private readonly SAMLSettings $samlSettings,
		private readonly IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this->setName('saml:config:validate');
		$this->setDescription('Check whether user_saml is correctly configured');
		parent::configure();
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$type = $this->appConfig->getAppValueString('type', '');
		if ($type === '') {
			$output->writeln('<comment>user_saml is not active (type not set). Configure it via the admin panel.</comment>');
			return 1;
		}
		$output->writeln('Type: <info>' . $type . '</info>');

		$idps = $this->samlSettings->getListOfIdps();
		if (empty($idps)) {
			$output->writeln('<error>No IdP providers configured. Add one via the admin panel or occ saml:config:create.</error>');
			return 2;
		}

		$exitCode = 0;
		foreach ($idps as $id => $name) {
			$cfg = $this->samlSettings->get($id);
			$missing = [];
			foreach (self::REQUIRED_FIELDS as $field) {
				if (empty($cfg[$field])) {
					$missing[] = $field;
				}
			}
			$label = "IdP #{$id}" . ($name !== '' ? " ({$name})" : '');
			if (empty($missing)) {
				$output->writeln("  {$label}: <info>OK</info>");
			} else {
				$output->writeln("  {$label}: <error>MISSING: " . implode(', ', $missing) . '</error>');
				$exitCode = 2;
			}
		}

		return $exitCode;
	}
}
