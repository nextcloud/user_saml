<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\SAMLSettings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGet extends Base {

	public function __construct(
		private readonly SAMLSettings $samlSettings,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('saml:config:get');

		$this->addOption(
			'providerId',
			'p',
			InputOption::VALUE_REQUIRED,
			'ProviderID of a SAML config to print'
		);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$providerId = (int)$input->getOption('providerId');
		if (!empty($providerId)) {
			$providerIds = [$providerId];
		} else {
			$providerIds = array_keys($this->samlSettings->getListOfIdps());
		}

		$settings = [];
		foreach ($providerIds as $pid) {
			$settings[$pid] = $this->samlSettings->get($pid);
		}

		$this->writeArrayInOutputFormat($input, $output, $settings);

		return 0;
	}
}
