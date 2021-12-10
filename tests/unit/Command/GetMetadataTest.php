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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

class GetMetadataTest extends \Test\TestCase {

	/** @var GetMetadata|\PHPUnit_Framework_MockObject_MockObject*/
	protected $GetMetadata;
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var SAMLSettings|\PHPUnit_Framework_MockObject_MockObject*/
	private $samlSettings;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;


	protected function setUp(): void {
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);

		$this->samlSettings = new SAMLSettings($this->urlGenerator,
								$this->config,
								$this->request,
								$this->session);
		$this->GetMetadata = new GetMetadata($this->samlSettings);

		parent::setUp();
	}

	public function testGetMetadata() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);
		$this->urlGenerator
			->expects($this->at(0))
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.base')
			->willReturn('https://nextcloud.com/base/');
		$this->urlGenerator
			->expects($this->at(1))
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.getMetadata')
			->willReturn('https://nextcloud.com/metadata/');
		$this->urlGenerator
			->expects($this->at(2))
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.assertionConsumerService')
			->willReturn('https://nextcloud.com/acs/');
		$this->config->expects($this->any())->method('getAppValue')
			 ->willReturnCallback(function ($app, $key, $default) {
			 	if ($key == 'idp-entityId') {
			 		return "dummy";
			 	}
			 	if ($key == 'idp-singleSignOnService.url') {
			 		return "https://example.com/sso";
			 	}
			 	if ($key == 'idp-x509cert') {
			 		return "DUMMY CERTIFICATE";
			 	}
			 	return $default;
			 });

		$outputInterface->expects($this->once())->method('writeln')
			  ->with($this->stringContains('md:EntityDescriptor'));

		$this->invokePrivate($this->GetMetadata, 'execute', [$inputInterface, $outputInterface]);
	}
}
