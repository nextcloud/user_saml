<?php

declare(strict_types=1);

namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version2500Date20191008134400 extends SimpleMigrationStep {

	/**
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('user_saml_users')) {
			$table = $schema->createTable('user_saml_users');
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('displayname', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('home', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->setPrimaryKey(['uid']);
		}

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

		if (!$schema->hasTable('user_saml_auth_token')) {
			$table = $schema->createTable('user_saml_auth_token');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('name', Types::TEXT, [
				'notnull' => true,
				'default' => '',
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 200,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
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
			$table->addUniqueIndex(['gid', 'uid'], 'idx_group_members');
		}
		return $schema;
	}
}
