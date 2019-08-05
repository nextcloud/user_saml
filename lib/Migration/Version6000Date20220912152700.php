<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Dominik Ach <da@infodatacom.de>
 *
 * @author Dominik Ach <da@infodatacom.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Maximilian Ruta <mr@xtain.net>
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Giuliano Mele <giuliano.mele@verdigado.com>
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
			$table->setPrimaryKey(['gid', 'uid'], 'idx_group_members');
			$table->addIndex(['gid']);
			$table->addIndex(['uid']);
		}
		return $schema;
	}
}
