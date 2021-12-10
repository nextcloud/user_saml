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

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OneLogin\Saml2\Constants;
use PHPUnit\Framework\MockObject\MockObject;

class AdminTest extends \Test\TestCase {
	/** @var SAMLSettings|MockObject */
	private $settings;
	/** @var Admin */
	private $admin;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	protected function setUp(): void {
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->config = $this->createMock(IConfig::class);
		$this->settings = $this->createMock(SAMLSettings::class);

		$this->admin = new Admin(
			$this->l10n,
			$this->defaults,
			$this->config,
			$this->settings
		);

		parent::setUp();
	}

	public function formDataProvider() {
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

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
			'wantXMLValidation' => 'Indicates if the SP will validate all received XML.',
		];
		$securityGeneral = [
			'lowercaseUrlencoding' => 'ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification.',
			'signatureAlgorithm' => [
				'type' => 'line',
				'text' => 'Algorithm that the toolkit will use on signing process.'
			],
			'sloWebServerDecode' => 'Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests.',
		];
		$generalSettings = [
			'idp0_display_name' => [
				'text' => $this->l10n->t('Optional display name of the identity provider (default: "SSO & SAML log in")'),
				'type' => 'line',
				'required' => false,
			],
			'uid_mapping' => [
				'text' => 'Attribute to map the UID to.',
				'type' => 'line',
				'required' => true,
			],
			'require_provisioned_account' => [
				'text' => 'Only allow authentication if an account exists on some other backend. (e.g. LDAP)',
				'type' => 'checkbox',
				'global' => true,
			],
			'use_saml_auth_for_desktop' => [
				'text' => 'Use SAML auth for the Nextcloud desktop clients (requires user re-authentication)',
				'type' => 'checkbox',
				'global' => true,
			],
			'allow_multiple_user_back_ends' => [
				'text' => $this->l10n->t('Allow the use of multiple user back-ends (e.g. LDAP)'),
				'type' => 'checkbox',
				'global' => true,
				'hideForEnv' => true,
			],
		];
		$attributeMappingSettings = [
			'displayName_mapping' => [
				'text' => $this->l10n->t('Attribute to map the displayname to.'),
				'type' => 'line',
				'required' => true,
			],
			'email_mapping' => [
				'text' => $this->l10n->t('Attribute to map the email address to.'),
				'type' => 'line',
				'required' => true,
			],
			'quota_mapping' => [
				'text' => $this->l10n->t('Attribute to map the quota to.'),
				'type' => 'line',
				'required' => false,
			],
			'group_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users groups to.'),
				'type' => 'line',
				'required' => true,
			],
			'home_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users home to.'),
				'type' => 'line',
				'required' => true,
			],
		];

		$nameIdFormats = [
			Constants::NAMEID_EMAIL_ADDRESS => [
				'label' => 'Email address',
				'selected' => false,
			],
			Constants::NAMEID_ENCRYPTED => [
				'label' => 'Encrypted',
				'selected' => false,
			],
			Constants::NAMEID_ENTITY => [
				'label' => 'Entity',
				'selected' => false,
			],
			Constants::NAMEID_KERBEROS => [
				'label' => 'Kerberos',
				'selected' => false,
			],
			Constants::NAMEID_PERSISTENT => [
				'label' => 'Persistent',
				'selected' => false,
			],
			Constants::NAMEID_TRANSIENT => [
				'label' => 'Transient',
				'selected' => false,
			],
			Constants::NAMEID_UNSPECIFIED => [
				'label' => 'Unspecified',
				'selected' => false,
			],
			Constants::NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME => [
				'label' => 'Windows domain qualified name',
				'selected' => false,
			],
			Constants::NAMEID_X509_SUBJECT_NAME => [
				'label' => 'X509 subject name',
				'selected' => false,
			],
		];

		$params = [
			'sp' => $serviceProviderFields,
			'security-offer' => $securityOfferFields,
			'security-required' => $securityRequiredFields,
			'security-general' => $securityGeneral,
			'general' => $generalSettings,
			'attribute-mapping' => $attributeMappingSettings,
			'providers' => [
				['id' => 1, 'name' => 'Provider 1'],
				['id' => 2, 'name' => 'Provider 2']
			],
			'name-id-formats' => $nameIdFormats,
		];

		return $params;
	}

	public function testGetFormWithoutType() {
		$this->settings->expects($this->once())
			->method('getListOfIdps')
			->willReturn([
				1 => 'Provider 1',
				2 => 'Provider 2',
			]);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('');

		$params = $this->formDataProvider();
		unset($params['general']['use_saml_auth_for_desktop']);
		unset($params['general']['idp0_display_name']);
		unset($params['general']['allow_multiple_user_back_ends']);
		$params['type'] = '';

		$expected = new TemplateResponse('user_saml', 'admin', $params);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithSaml() {
		$this->settings->expects($this->once())
			->method('getListOfIdps')
			->willReturn([
				1 => 'Provider 1',
				2 => 'Provider 2',
			]);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('saml');
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('Nextcloud');

		$params = $this->formDataProvider();
		$params['type'] = 'saml';

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
