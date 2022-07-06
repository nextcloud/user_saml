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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGet extends Base {

	/** @var SAMLSettings */
	private $samlSettings;

	public function __construct(SAMLSettings $samlSettings) {
		parent::__construct();
		$this->samlSettings = $samlSettings;
	}

	protected function configure() {
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
