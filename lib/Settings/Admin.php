<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Settings;

use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OneLogin\Saml2\Constants;

class Admin implements ISettings {

	public function __construct(
		private readonly IL10N $l10n,
		private readonly Defaults $defaults,
		private readonly IConfig $config,
		private readonly SAMLSettings $samlSettings,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$providerIds = $this->samlSettings->getListOfIdps();
		$providers = [];
		foreach ($providerIds as $id => $name) {
			$providers[] = [
				'id' => $id,
				'name' => $name === '' ? $this->l10n->t('Provider %s', [$id])  : $name
			];
		}
		$serviceProviderFields = [
			'x509cert' => [
				'text' => $this->l10n->t('X.509 certificate of the Service Provider'),
				'type' => 'text',
				'required' => false,
			],
			'privateKey' => [
				'text' => $this->l10n->t('Private key of the Service Provider'),
				'type' => 'text',
				'required' => false,
			],
			'entityId' => [
				'text' => $this->l10n->t('Service Provider Entity ID (optional)'),
				'type' => 'line',
				'required' => false,
			]
		];
		$securityOfferFields = [
			'nameIdEncrypted' => $this->l10n->t('Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted.'),
			'authnRequestsSigned' => $this->l10n->t('Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]'),
			'logoutRequestSigned' => $this->l10n->t('Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed.'),
			'logoutResponseSigned' => $this->l10n->t('Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed.'),
			'signMetadata' => $this->l10n->t('Whether the metadata should be signed.')
		];
		$securityRequiredFields = [
			'wantMessagesSigned' => $this->l10n->t('Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed.'),
			'wantAssertionsSigned' => $this->l10n->t('Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]'),
			'wantAssertionsEncrypted' => $this->l10n->t('Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted.'),
			'wantNameId' => $this->l10n->t(' Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present.'),
			'wantNameIdEncrypted' => $this->l10n->t('Indicates a requirement for the NameID received by this SP to be encrypted.'),
			'wantXMLValidation' => $this->l10n->t('Indicates if the SP will validate all received XML.'),
		];
		$securityGeneral = [
			'lowercaseUrlencoding' => $this->l10n->t('ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification.'),
			'signatureAlgorithm' => [
				'type' => 'line',
				'text' => $this->l10n->t('Algorithm that the toolkit will use on signing process.')
			],
			'sloWebServerDecode' => $this->l10n->t('Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests.'),
		];
		$generalSettings = [
			'uid_mapping' => [
				'text' => $this->l10n->t('Attribute to map the UID to.'),
				'type' => 'line',
				'required' => true,
			],
			'require_provisioned_account' => [
				'text' => $this->l10n->t('Only allow authentication if an account exists on some other backend (e.g. LDAP).'),
				'type' => 'checkbox',
				'global' => true,
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
			'home_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users home to.'),
				'type' => 'line',
				'required' => true,
			],
			'group_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users groups to.'),
				'type' => 'line',
				'required' => true,
			],
			'mfa_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users MFA login status'),
				'type' => 'line',
				'required' => false,
			],
			'group_mapping_prefix' => [
				'text' => $this->l10n->t('Group Mapping Prefix, default: %s', [SAMLSettings::DEFAULT_GROUP_PREFIX]),
				'type' => 'line',
				'required' => true,
			],
		];

		$userFilterSettings = [
			'reject_groups' => [
				'text' => $this->l10n->t('Reject members of these groups. This setting has precedence over required memberships.'),
				'placeholder' => $this->l10n->t('Group A, Group B, …'),
				'type' => 'line',
				'required' => true,
			],
			'require_groups' => [
				'text' => $this->l10n->t('Require membership in these groups, if any.'),
				'placeholder' => $this->l10n->t('Group A, Group B, …'),
				'type' => 'line',
				'required' => true,
			],
		];

		$firstIdPConfig = isset($providers[0]) ? $this->samlSettings->get($providers[0]['id']) : null;
		$nameIdFormats = [
			Constants::NAMEID_EMAIL_ADDRESS => [
				'label' => $this->l10n->t('Email address'),
				'selected' => false,
			],
			Constants::NAMEID_ENCRYPTED => [
				'label' => $this->l10n->t('Encrypted'),
				'selected' => false,
			],
			Constants::NAMEID_ENTITY => [
				'label' => $this->l10n->t('Entity'),
				'selected' => false,
			],
			Constants::NAMEID_KERBEROS => [
				'label' => $this->l10n->t('Kerberos'),
				'selected' => false,
			],
			Constants::NAMEID_PERSISTENT => [
				'label' => $this->l10n->t('Persistent'),
				'selected' => false,
			],
			Constants::NAMEID_TRANSIENT => [
				'label' => $this->l10n->t('Transient'),
				'selected' => false,
			],
			Constants::NAMEID_UNSPECIFIED => [
				'label' => $this->l10n->t('Unspecified'),
				'selected' => false,
			],
			Constants::NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME => [
				'label' => $this->l10n->t('Windows domain qualified name'),
				'selected' => false,
			],
			Constants::NAMEID_X509_SUBJECT_NAME => [
				'label' => $this->l10n->t('X509 subject name'),
				'selected' => false,
			],
		];
		$chosenFormat = $firstIdPConfig['sp-name-id-format'] ?? '';
		if ($firstIdPConfig !== null && isset($nameIdFormats[$chosenFormat])) {
			$nameIdFormats[$chosenFormat]['selected'] = true;
		} else {
			$nameIdFormats[Constants::NAMEID_UNSPECIFIED]['selected'] = true;
		}

		$type = $this->config->getAppValue('user_saml', 'type');

		$generalSettings['require_provisioned_account'] = [
			'text' => $this->l10n->t('Only allow authentication if an account exists on some other backend (e.g. LDAP).', [$this->defaults->getName()]),
			'type' => 'checkbox',
			'global' => true,
			'value' => $this->config->getAppValue('user_saml', 'general-require_provisioned_account', '0')
		];
		if ($type === 'saml') {
			$generalSettings['idp0_display_name'] = [
				'text' => $this->l10n->t('Optional display name of the identity provider (default: "SSO & SAML log in")'),
				'type' => 'line',
				'required' => false,
			];
			$generalSettings['is_saml_request_using_post'] = [
				'text' => $this->l10n->t('Use POST method for SAML request (default: GET)'),
				'type' => 'checkbox',
				'required' => false,
				'global' => false,
			];
			$generalSettings['allow_multiple_user_back_ends'] = [
				'text' => $this->l10n->t('Allow the use of multiple user back-ends (e.g. LDAP)'),
				'type' => 'checkbox',
				'hideForEnv' => true,
				'global' => true,
				'value' => $this->config->getAppValue('user_saml', 'general-allow_multiple_user_back_ends', '0')
			];
		}

		$params = [
			'sp' => $serviceProviderFields,
			'security-offer' => $securityOfferFields,
			'security-required' => $securityRequiredFields,
			'security-general' => $securityGeneral,
			'general' => $generalSettings,
			'attribute-mapping' => $attributeMappingSettings,
			'user-filter' => $userFilterSettings,
			'name-id-formats' => $nameIdFormats,
			'type' => $type,
			'providers' => $providers,
			'config' => $firstIdPConfig,
		];

		return new TemplateResponse('user_saml', 'admin', $params);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'saml';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority() {
		return 0;
	}
}
