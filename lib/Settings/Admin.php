<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Settings;

use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;
use OneLogin\Saml2\Constants;
use Override;

class Admin implements IDelegatedSettings {
	public function __construct(
		private readonly IL10N $l10n,
		private readonly Defaults $defaults,
		private readonly IAppConfig $appConfig,
		private readonly SAMLSettings $samlSettings,
		private readonly IInitialState $initialState,
	) {
	}

	#[Override]
	public function getForm(): TemplateResponse {
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
				'provider_type' => '',
			],
			'require_provisioned_account' => [
				'text' => $this->l10n->t('Only allow authentication if an account exists on some other backend (e.g. LDAP).', [$this->defaults->getName()]),
				'type' => 'checkbox',
				'global' => true,
				'value' => $this->appConfig->getAppValueBool('general-require_provisioned_account'),
				'provider_type' => '',
			],
			'idp0_display_name' => [
				'text' => $this->l10n->t('Optional display name of the identity provider (default: "SSO & SAML log in")'),
				'type' => 'line',
				'required' => false,
				'provider_type' => '',
			],
			'is_saml_request_using_post' => [
				'text' => $this->l10n->t('Use POST method for SAML request (default: GET)'),
				'type' => 'checkbox',
				'required' => false,
				'global' => false,
				'provider_type' => 'saml',
			],
			'allow_multiple_user_back_ends' => [
				'text' => $this->l10n->t('Allow the use of multiple user back-ends (e.g. LDAP)'),
				'type' => 'checkbox',
				'global' => true,
				'value' => $this->appConfig->getAppValueBool('general-allow_multiple_user_back_ends'),
				'provider_type' => '',
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
				'required' => false,
			],
			'group_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users groups to.'),
				'type' => 'line',
				'required' => false,
			],
			'mfa_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users MFA login status'),
				'type' => 'line',
				'required' => false,
			],
			'group_mapping_prefix' => [
				'text' => $this->l10n->t('Group Mapping Prefix, default: %s', [SAMLSettings::DEFAULT_GROUP_PREFIX]),
				'type' => 'line',
				'required' => false,
			],
		];

		$userFilterSettings = [
			'reject_groups' => [
				'text' => $this->l10n->t('Reject members of these groups. This setting has precedence over required memberships.'),
				'placeholder' => $this->l10n->t('Group A, Group B, …'),
				'type' => 'line',
				'required' => false,
			],
			'require_groups' => [
				'text' => $this->l10n->t('Require membership in these groups, if any.'),
				'placeholder' => $this->l10n->t('Group A, Group B, …'),
				'type' => 'line',
				'required' => false,
			],
		];

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
				'selected' => true,
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

		$type = $this->appConfig->getAppValueString('type');

		$globalConfig = [];
		foreach ($generalSettings as $key => $attribute) {
			if (isset($attribute['global']) && $attribute['global']) {
				$globalConfig[$key] = $attribute['value'] ?? '0';
			}
		}

		$this->initialState->provideInitialState('type', $type);
		$this->initialState->provideInitialState('providers', $providers);
		$this->initialState->provideInitialState('generalSettings', $generalSettings);
		$this->initialState->provideInitialState('spSettings', $serviceProviderFields);
		$this->initialState->provideInitialState('nameIdFormats', $nameIdFormats);
		$this->initialState->provideInitialState('attributeMappingSettings', $attributeMappingSettings);
		$this->initialState->provideInitialState('securityOffer', $securityOfferFields);
		$this->initialState->provideInitialState('securityRequired', $securityRequiredFields);
		$this->initialState->provideInitialState('securityGeneral', $securityGeneral);
		$this->initialState->provideInitialState('userFilterSettings', $userFilterSettings);
		$this->initialState->provideInitialState('globalConfig', $globalConfig);

		// Only used in unit tests
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
		];

		return new TemplateResponse('user_saml', 'admin', $params);
	}

	#[Override]
	public function getSection(): string {
		return 'saml';
	}

	#[Override]
	public function getPriority(): int {
		return 0;
	}

	#[Override]
	public function getName(): ?string {
		return $this->l10n->t('SSO & SAML authentication');
	}

	#[Override]
	public function getAuthorizedAppConfig(): array {
		return [
			'user_saml' => [
				'type',
				'general-require_provisioned_account',
				'general-allow_multiple_user_back_ends',
				'directLoginName',
			],
		];
	}
}
