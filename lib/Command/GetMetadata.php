<?php
/**
 * @copyright Copyright (c) 2020 Maxime Besson <maxime.besson@worteks.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\User_SAML\SAMLSettings;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;

class GetMetadata extends Command {

	/** @var SAMLSettings */
	private $SAMLSettings;

	public function __construct(
		SAMLSettings $SAMLSettings
	) {
		parent::__construct();
		$this->SAMLSettings = $SAMLSettings;
	}

	protected function configure() {
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

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$idp = (int)$input->getArgument('idp');
		$settings = new Settings($this->SAMLSettings->getOneLoginSettingsArray($idp));
		$metadata = $settings->getSPMetadata();
		$errors = $settings->validateMetadata($metadata);
		if (empty($errors)) {
			$output->writeln($metadata);
		} else {
			throw new Error(
				'Invalid SP metadata: '.implode(', ', $errors),
				Error::METADATA_SP_INVALID
			);
		}
	}
}
