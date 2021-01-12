<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3001Date20200630193443 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
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
		return $schema;
	}
}
