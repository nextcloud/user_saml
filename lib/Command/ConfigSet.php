<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\SAMLSettings;
use OCP\DB\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigSet extends Base {

	public function __construct(
		private readonly SAMLSettings $samlSettings,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('saml:config:set');

		$this->addArgument(
			'providerId',
			InputArgument::REQUIRED,
			'ProviderID of the SAML config to edit'
		);

		foreach (SAMLSettings::IDP_CONFIG_KEYS as $key) {
			$this->addOption(
				$key,
				null,
				InputOption::VALUE_REQUIRED,
			);
		}

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$pId = (int)$input->getArgument('providerId');

		if ((string)$pId !== $input->getArgument('providerId')) {
			// Make sure we don't delete provider with id 0 by error
			$output->writeln('<error>providerId argument needs to be an number. Got: ' . $pId . '</error>');
			return 1;
		}
		try {
			$settings = $this->samlSettings->get($pId);
		} catch (Exception) {
			$output->writeln('<error>Provider with id: ' . $pId . ' does not exist.</error>');
			return 1;
		}

		foreach ($input->getOptions() as $key => $value) {
			if (!in_array($key, SAMLSettings::IDP_CONFIG_KEYS) || $value === null) {
				continue;
			}
			if ($value === '') {
				unset($settings[$key]);
				continue;
			}
			$settings[$key] = $value;
		}
		$this->samlSettings->set($pId, $settings);
		$output->writeln('The provider\'s config was updated.');

		return 0;
	}
}
