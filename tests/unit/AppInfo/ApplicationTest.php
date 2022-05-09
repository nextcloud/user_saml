<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Tests\AppInfo;

use OCA\User_SAML\AppInfo\Application;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;

class ApplicationTest extends \Test\TestCase {
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
			[OnlyLoggedInMiddleware::class],
		];
	}

	/**
	 * @dataProvider queryData
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($serviceClass) {
		$this->assertTrue($this->container->query($serviceClass) instanceof $serviceClass);
	}
}
