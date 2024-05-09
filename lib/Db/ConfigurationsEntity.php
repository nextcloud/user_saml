<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
