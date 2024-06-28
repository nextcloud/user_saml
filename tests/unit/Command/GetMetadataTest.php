<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Command;

use OCA\User_SAML\Command\GetMetadata;
use OCA\User_SAML\SAMLSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetMetadataTest extends \Test\TestCase {

	/** @var GetMetadata|MockObject */
	protected $GetMetadata;
	/** @var SAMLSettings|MockObject */
	private $samlSettings;

	protected function setUp(): void {
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->GetMetadata = new GetMetadata($this->samlSettings);

		parent::setUp();
	}
	public function testGetMetadata() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface->expects($this->any())
			->method('getArgument')
			->with('idp')
			->willReturn('1');

		$this->samlSettings->expects($this->any())
			->method('getOneLoginSettingsArray')
			->willReturn([
				'baseurl' => 'https://nextcloud.com/base/',
				'idp' => [
					'entityId' => 'dummy',
					'singleSignOnService' => ['url' => 'https://example.com/sso'],
					'x509cert' => 'DUMMY CERTIFICATE',
				],
				'sp' => [
					'entityId' => 'https://nextcloud.com/metadata/',
					'assertionConsumerService' => [
						'url' => 'https://nextcloud.com/acs/',
					],
				]
			]);

		$outputInterface->expects($this->once())
			->method('writeln')
			->with($this->stringContains('md:EntityDescriptor'));

		$result = $this->invokePrivate($this->GetMetadata, 'execute', [$inputInterface, $outputInterface]);

		$this->assertEquals(0, $result);
	}
}
