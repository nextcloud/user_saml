<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OCA\User_SAML\Tests\AppInfo;

use OCA\User_SAML\AppInfo\Application;
use OCA\User_SAML\Controller\SAMLController;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;

class ApplicationTest extends \Test\TestCase  {
	/** @var Application */
	protected $app;
	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName() {
		$this->app = new Application();
		$this->assertEquals('user_saml', $this->container->getAppName());
	}

	public function queryData() {
		return [
			['OnlyLoggedInMiddleware', OnlyLoggedInMiddleware::class],
		];

	}

	/**
	 * @dataProvider queryData
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected = null) {
		if ($expected === null) {
			$expected = $service;
		}
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}
}
