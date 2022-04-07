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

namespace OCA\User_SAML\Db;

use OCP\AppFramework\Db\Entity;
use function json_decode;
use function json_encode;

/**
 * @method string getName()
 * @method void setName(string $value)
 * @method void setConfiguration(string $value)
 * @method string getConfiguration()
 */
class ConfigurationsEntity extends Entity {
	/** @var string */
	public $name;
	/** @var string */
	public $configuration;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('configuration', 'string');
	}

	/**
	 * sets also the name, because it is a shorthand to 'general-idp0_display_name'
	 *
	 * @throws \JsonException
	 */
	public function importConfiguration(array $configuration): void {
		$this->setConfiguration(json_encode($configuration, JSON_THROW_ON_ERROR));
		$this->setName($configuration['general-idp0_display_name'] ?? '');
	}

	public function getConfigurationArray(): array {
		return json_decode($this->configuration, true) ?? [];
	}

	public function asArray(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'configuration' => $this->getConfigurationArray()
		];
	}
}
