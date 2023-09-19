<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\SAMLSettings;
use OCP\DB\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigDelete extends Base {
	private SAMLSettings $samlSettings;

	public function __construct(SAMLSettings $samlSettings) {
		parent::__construct();
		$this->samlSettings = $samlSettings;
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
		} catch (Exception $e) {
			$output->writeln('<error>Provider with id: ' . $providerId . ' does not exist.</error>');
			return 1;
		}
		return 0;
	}
}
