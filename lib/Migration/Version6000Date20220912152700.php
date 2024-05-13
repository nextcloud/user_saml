<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version6000Date20220912152700 extends SimpleMigrationStep {

	/**
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('user_saml_groups')) {
			$table = $schema->createTable('user_saml_groups');
			$table->addColumn('gid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('displayname', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('saml_gid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->setPrimaryKey(['gid']);
			$table->addUniqueIndex(['saml_gid']);
		}

		if (!$schema->hasTable('user_saml_group_members')) {
			$table = $schema->createTable('user_saml_group_members');
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('gid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->setPrimaryKey(['gid', 'uid'], 'pk_saml_group_members');
			$table->addIndex(['gid'], 'saml_group_members_gid'); // prefix is added in callee
			$table->addIndex(['uid'], 'saml_group_members_uid'); // prefix is added in callee
		}
		return $schema;
	}
}
