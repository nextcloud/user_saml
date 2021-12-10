<?php
/**
 * @copyright Copyright (c) 2020 Maxime Besson <maxime.besson@worteks.com>
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

namespace OCA\User_SAML\Tests\Command;

use OCA\User_SAML\Command\GetMetadata;
use OCA\User_SAML\SAMLSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetMetadataTest extends \Test\TestCase {

	/** @var GetMetadata|MockObject*/
	protected $GetMetadata;
	/** @var SAMLSettings|MockObject*/
	private $samlSettings;

	protected function setUp(): void {
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->GetMetadata = new GetMetadata($this->samlSettings);

		parent::setUp();
	}
	public function testGetMetadata() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

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

		$outputInterface->expects($this->once())->method('writeln')
			->with($this->stringContains('md:EntityDescriptor'));

		$this->invokePrivate($this->GetMetadata, 'execute', [$inputInterface, $outputInterface]);
	}
}
