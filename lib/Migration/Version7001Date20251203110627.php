<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version7001Date20251203110627 extends SimpleMigrationStep {

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('user_saml_groups')) {
			return null;
		}

		$groupsTable = $schema->getTable('user_saml_groups');
		$samlGidColumn = $groupsTable->getColumn('saml_gid');
		if ($samlGidColumn->getLength() < 255) {
			$samlGidColumn->setLength(255);
			return $schema;
		}
		return null;
	}
}
