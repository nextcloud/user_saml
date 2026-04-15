<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Tests\AppInfo;

use OCA\User_SAML\AppInfo\Application;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;

class ApplicationTest extends \Test\TestCase {
	protected Application $app;
	protected ContainerInterface $container;

	#[Override]
	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function queryData(): array {
		return [
			[OnlyLoggedInMiddleware::class],
		];
	}

	#[DataProvider(methodName: 'queryData')]
	public function testContainerQuery(string $serviceClass): void {
		$this->assertTrue($this->container->get($serviceClass) instanceof $serviceClass);
	}
}
