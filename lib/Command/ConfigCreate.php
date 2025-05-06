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
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCreate extends Base {

	public function __construct(
		private readonly SAMLSettings $samlSettings,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('saml:config:create');
		$this->setDescription('Creates a new config and prints the new provider ID');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln((string)$this->samlSettings->getNewProviderId());
		return 0;
	}
}
