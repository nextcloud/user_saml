<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Db;

use OCA\User_SAML\Model\SessionData as SessionDataModel;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method getId(): string
 * @method setId(string $id): void
 * @method setTokenId(int $tokenId): void
 * @method getTokenId(): int
 */
class SessionData extends Entity {
	public ?string $data = null;
	protected ?int $tokenId = null;

	public function __construct() {
		$this->addType('id', Types::STRING);
		// technically tokenId is BIGINT, effectively no difference in the Entity context.
		// It can be set to BIGINT once dropping NC 30 support.
		$this->addType('tokenId', Types::INTEGER);
		$this->addType('data', Types::TEXT);
	}

	public function setData(SessionDataModel $input): void {
		$this->data = json_encode($input);
		$this->markFieldUpdated('data');
	}

	public function getData(): SessionDataModel {
		$deserialized = json_decode((string)$this->data, true);
		return SessionDataModel::fromInputArray($deserialized);
	}
}
