<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version7001Date20251215192613 extends SimpleMigrationStep {
	public const SESSION_DATA_TABLE_NAME = 'user_saml_session_data';

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(self::SESSION_DATA_TABLE_NAME)) {
			return null;
		}

		$table = $schema->createTable(self::SESSION_DATA_TABLE_NAME);
		$table->addColumn('id', Types::STRING, [
			'notnull' => true,
			'length' => 200,
		]);
		$table->addColumn('token_id', Types::BIGINT, [
			'notnull' => true,
		]);
		$table->addColumn('data', Types::TEXT, [
			'notnull' => true,
		]);
		$table->setPrimaryKey(['id']);

		return $schema;
	}

}
