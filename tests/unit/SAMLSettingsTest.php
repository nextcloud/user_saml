<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\Db\ConfigurationsMapper;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\ISession;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SAMLSettingsTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private ISession&MockObject $session;
	private ConfigurationsMapper&MockObject $mapper;
	private SAMLSettings $samlSettings;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->mapper = $this->createMock(ConfigurationsMapper::class);

		$this->samlSettings = new SAMLSettings(
			$this->urlGenerator,
			$this->config,
			$this->appConfig,
			$this->session,
			$this->mapper,
		);
	}

	private static function dataGetListOfIdps(): array {
		return [
			'empty-all' => [
				false,
				[],
				[],
			],
			'empty-complete' => [
				true,
				[],
				[],
			],
			'entityId-and-ssoUrl-missing' => [
				true,
				[
					1 => [
						'general-idp0_display_name' => 'My IdP',
						// no idp-entityId, no idp-singleSignOnService.url
					],
				],
				[],
			],
			'only-whitespace' => [
				true,
				[
					1 => [
						'general-idp0_display_name' => 'My IdP',
						'idp-entityId' => '   ',
						'idp-singleSignOnService.url' => "\t",
					],
				],
				[],
			],
			'configured' => [
				true,
				[
					1 => [
						'general-idp0_display_name' => 'My IdP',
						'idp-entityId' => 'https://idp.example.com',
						'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
					],
				],
				[1 => 'My IdP'],
			],
			'partially-configured' => [
				true,
				[
					1 => [
						'general-idp0_display_name' => 'Configured IdP',
						'idp-entityId' => 'https://idp.example.com',
						'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
					],
					2 => [
						'general-idp0_display_name' => 'Missing SSO URL',
						'idp-entityId' => 'https://idp2.example.com',
						// missing idp-singleSignOnService.url
					],
					3 => [
						'general-idp0_display_name' => 'Missing Entity ID',
						// missing idp-entityId
						'idp-singleSignOnService.url' => 'https://idp3.example.com/sso',
					],
				],
				[1 => 'Configured IdP'],
			],
		];
	}

	#[DataProvider('dataGetListOfIdps')]
	public function testGetListOfIdps(bool $onlyComplete, array $configs, array $expected): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn($configs);

		$result = $this->samlSettings->getListOfIdps($onlyComplete);

		$this->assertSame($expected, $result);
	}
}
