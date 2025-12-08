<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			->willReturnCallback(fn ($text, $parameters = []) => vsprintf($text, $parameters));

		$serviceProviderFields = [
			'x509cert' => [
				'text' => 'X.509 certificate of the Service Provider',
				'type' => 'text',
				'required' => false,
			],
			'privateKey' => [
				'text' => 'Private key of the Service Provider',
				'type' => 'text',
				'required' => false,
			],
			'entityId' => [
				'text' => 'Service Provider Entity ID (optional)',
				'type' => 'line',
				'required' => false,
			]
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
			'is_saml_request_using_post' => [
				'text' => $this->l10n->t('Use POST method for SAML request (default: GET)'),
				'type' => 'checkbox',
				'required' => false,
				'global' => false,
			],
			'uid_mapping' => [
				'text' => 'Attribute to map the UID to.',
				'type' => 'line',
				'required' => true,
			],
			'require_provisioned_account' => [
				'text' => 'Only allow authentication if an account exists on some other backend (e.g. LDAP).',
				'type' => 'checkbox',
				'global' => true,
				'value' => '0'
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
			'mfa_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users MFA login status'),
				'type' => 'line',
				'required' => false,
			],
			'group_mapping_prefix' => [
				'text' => $this->l10n->t('Group Mapping Prefix, default: SAML_'),
				'type' => 'line',
				'required' => true,
			],
		];

		$userFilterSettings = [
			'reject_groups' => [
				'text' => 'Reject members of these groups. This setting has precedence over required memberships.',
				'placeholder' => 'Group A, Group B, …',
				'type' => 'line',
				'required' => true,
			],
			'require_groups' => [
				'text' => 'Require membership in these groups, if any.',
				'placeholder' => 'Group A, Group B, …',
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
				'selected' => true,
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
			'user-filter' => $userFilterSettings,
			'providers' => [
				['id' => 1, 'name' => 'Provider 1'],
				['id' => 2, 'name' => 'Provider 2']
			],
			'name-id-formats' => $nameIdFormats,
			'config' => [],
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
			->expects($this->exactly(2)) // 'type' and 'general-require_provisioned_account'
			->method('getAppValue')
			->with('user_saml', $this->anything(), $this->anything())
			->willReturn($this->returnArgument(2));

		$params = $this->formDataProvider();
		unset($params['general']['idp0_display_name'], $params['general']['is_saml_request_using_post'], $params['general']['allow_multiple_user_back_ends']);
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
			->expects($this->exactly(3)) # mode + three global values
			->method('getAppValue')
			->withConsecutive(
				['user_saml', 'type'],
				['user_saml', 'general-require_provisioned_account'],
				['user_saml', 'general-allow_multiple_user_back_ends'],
			)
			->willReturnOnConsecutiveCalls('saml', '0', '0');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('Nextcloud');

		$params = $this->formDataProvider();
		$params['type'] = 'saml';
		$params['general']['require_provisioned_account']['value'] = '0';
		$params['general']['allow_multiple_user_back_ends']['value'] = '0';

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
