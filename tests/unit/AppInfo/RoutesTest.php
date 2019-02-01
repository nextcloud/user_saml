<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\User_OIDC\Tests\AppInfo;

use Test\TestCase;

class Test extends TestCase  {
	public function testFile() {
		$routes = require_once __DIR__ . '/../../../appinfo/routes.php';

		$expected = [
			'routes' => [
				[
					'name' => 'OIDC#login',
					'url' => '/oidc/login',
					'verb' => 'GET',
				],
				[
					'name' => 'OIDC#base',
					'url' => '/oidc',
					'verb' => 'GET',
				],
				[
					'name' => 'OIDC#signOut',
					'url' => '/oidc/signout',
					'verb' => 'GET',
				],
				[
					'name' => 'OIDC#notProvisioned',
					'url' => '/oidc/notProvisioned',
					'verb' => 'GET',
				],
				[
					'name' => 'OIDC#genericError',
					'url' => '/oidc/error',
					'verb' => 'GET',
				],
			],
		];
		$this->assertSame($expected, $routes);
	}
}
