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
use Symfony\Component\Console\Output\OutputInterface;

class ConfigDelete extends Base {

	public function __construct(
		private readonly SAMLSettings $samlSettings,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('saml:config:delete');

		$this->addArgument(
			'providerId',
			InputArgument::REQUIRED,
			'ProviderID of the SAML config to edit'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$pId = (int)$input->getArgument('providerId');

		if ((string)$pId !== $input->getArgument('providerId')) {
			// Make sure we don't delete provider with id 0 by error
			$output->writeln('<error>providerId argument needs to be an number. Got: ' . $pId . '</error>');
			return 1;
		}
		try {
			$this->samlSettings->delete($pId);
			$output->writeln('Provider deleted.');
		} catch (Exception) {
			$output->writeln('<error>Provider with id: ' . $pId . ' does not exist.</error>');
			return 1;
		}
		return 0;
	}
}
