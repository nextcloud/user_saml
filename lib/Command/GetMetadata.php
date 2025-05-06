<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Command;

use OCA\User_SAML\Helper\TXmlHelper;
use OCA\User_SAML\SAMLSettings;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetMetadata extends Command {
	use TXmlHelper;

	public function __construct(
		private SAMLSettings $samlSettings,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('saml:metadata')
			->setDescription('Get SAML Metadata')
			->setHelp(<<<EOT
This command prints out the Nextcloud SAML Metadata for this provider.

It may require setting overwrite.cli.url and htaccess.IgnoreFrontController to
generate the correct URLs and entityID
EOT
			)

			->addArgument(
				'idp',
				InputArgument::OPTIONAL,
				'ID of the IDP you want metadata for',
				'1'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$idp = (int)$input->getArgument('idp');
		$settingsArray = $this->samlSettings->getOneLoginSettingsArray($idp);
		$settings = new Settings($settingsArray);
		$metadata = $settings->getSPMetadata();
		$errors = $this->callWithXmlEntityLoader(fn () => $settings->validateMetadata($metadata));
		if (empty($errors)) {
			$output->writeln($metadata);
		} else {
			throw new Error(
				'Invalid SP metadata: ' . implode(', ', $errors),
				Error::METADATA_SP_INVALID
			);
		}
		return 0;
	}
}
