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

namespace OCA\User_SAML\Tests\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IL10N;

class AdminTest extends \Test\TestCase  {
	/** @var \OCA\User_SAML\Settings\Admin */
	private $admin;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;

	public function setUp() {
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaults = $this->createMock(Defaults::class);

		$this->admin = new \OCA\User_SAML\Settings\Admin(
			$this->l10n,
			$this->defaults
		);

		return parent::setUp();
	}

	public function testGetForm() {
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('Nextcloud');

		$serviceProviderFields = [
			'x509cert' => 'X.509 certificate of the Service Provider',
			'privateKey' => 'Private key of the Service Provider',
		];
		$securityOfferFields = [
			'nameIdEncrypted' => 'Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted.',
			'authnRequestsSigned' => 'Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]',
			'logoutRequestSigned' => 'Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed.',
			'logoutResponseSigned' => 'Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed.',
			'signMetadata' => 'Whether the metadata should be signed.',
		];
		$securityRequiredFields = [
			'wantMessagesSigned' => 'Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed.',
			'wantAssertionsSigned' => 'Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]',
			'wantAssertionsEncrypted' => 'Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted.',
			'wantNameId' => ' Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present.',
			'wantNameIdEncrypted' => 'Indicates a requirement for the NameID received by this SP to be encrypted.',
			'wantXMLValidation' => 'Indicates if the SP will validate all received XMLs.',
		];
		$generalSettings = [
			'uid_mapping' => [
				'text' => 'Attribute to map the UID to.',
				'type' => 'line',
				'required' => true,
			],
			'require_provisioned_account' => [
				'text' => 'Only allow authentication if an account is existent on some other backend. (e.g. LDAP)',
				'type' => 'checkbox',
			],
			'use_saml_auth_for_desktop' => [
				'text' => 'Use SAML auth for the Nextcloud desktop clients (requires user re-authentication)',
				'type' => 'checkbox',
			],
		];

		$params = [
			'sp' => $serviceProviderFields,
			'security-offer' => $securityOfferFields,
			'security-required' => $securityRequiredFields,
			'general' => $generalSettings,
		];

		$expected = new TemplateResponse('user_saml', 'admin', $params);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('saml', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
