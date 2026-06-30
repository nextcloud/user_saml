<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Command;

use OCA\User_SAML\Command\ConfigValidate;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Services\IAppConfig;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ConfigValidateTest extends TestCase {
	private SAMLSettings&MockObject $samlSettings;
	private IAppConfig&MockObject $appConfig;
	private ConfigValidate $command;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->command = new ConfigValidate($this->samlSettings, $this->appConfig);
	}

	private function makeInput(): InputInterface {
		$input = $this->createMock(InputInterface::class);
		$input->method('getOption')->willReturn(null);
		$input->method('getArgument')->willReturn(null);
		return $input;
	}

	public function testExitsWithCodeOneWhenTypeNotSet(): void {
		$this->appConfig->expects($this->once())
			->method('getAppValueString')
			->with('type', '')
			->willReturn('');

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->once())
			->method('writeln')
			->with($this->stringContains('not active'));

		$exitCode = $this->invokePrivate($this->command, 'execute', [$this->makeInput(), $output]);

		$this->assertEquals(1, $exitCode);
	}

	public function testExitsWithCodeTwoWhenNoIdpsConfigured(): void {
		$this->appConfig->expects($this->once())
			->method('getAppValueString')
			->with('type', '')
			->willReturn('saml');

		$this->samlSettings->expects($this->once())
			->method('getListOfIdps')
			->willReturn([]);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->exactly(2))
			->method('writeln')
			->willReturnCallback(function (string $msg): void {
				// just capture; specific assertions follow via mock constraints
			});

		$exitCode = $this->invokePrivate($this->command, 'execute', [$this->makeInput(), $output]);

		$this->assertEquals(2, $exitCode);
	}

	public function testExitsZeroWhenFullyConfigured(): void {
		$this->appConfig->method('getAppValueString')
			->with('type', '')
			->willReturn('saml');

		$this->samlSettings->method('getListOfIdps')
			->willReturn([1 => 'My IdP']);

		$this->samlSettings->method('get')
			->with(1)
			->willReturn([
				'idp-entityId' => 'https://idp.example.com',
				'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
				'general-uid_mapping' => 'uid',
			]);

		$output = $this->createMock(OutputInterface::class);
		$messages = [];
		$output->method('writeln')
			->willReturnCallback(function (string $msg) use (&$messages): void {
				$messages[] = $msg;
			});

		$exitCode = $this->invokePrivate($this->command, 'execute', [$this->makeInput(), $output]);

		$this->assertEquals(0, $exitCode);
		$this->assertTrue(
			count(array_filter($messages, fn (string $m) => str_contains($m, 'OK'))) > 0,
			'Expected at least one "OK" message'
		);
	}

	public function testExitsWithCodeTwoWhenRequiredFieldsMissing(): void {
		$this->appConfig->method('getAppValueString')
			->with('type', '')
			->willReturn('saml');

		$this->samlSettings->method('getListOfIdps')
			->willReturn([1 => 'My IdP']);

		$this->samlSettings->method('get')
			->with(1)
			->willReturn([]); // all required fields missing

		$output = $this->createMock(OutputInterface::class);
		$messages = [];
		$output->method('writeln')
			->willReturnCallback(function (string $msg) use (&$messages): void {
				$messages[] = $msg;
			});

		$exitCode = $this->invokePrivate($this->command, 'execute', [$this->makeInput(), $output]);

		$this->assertEquals(2, $exitCode);

		$errorMessages = implode(' ', $messages);
		$this->assertStringContainsString('idp-entityId', $errorMessages);
		$this->assertStringContainsString('idp-singleSignOnService.url', $errorMessages);
		$this->assertStringContainsString('general-uid_mapping', $errorMessages);
	}
}
