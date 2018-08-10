<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\User_SAML\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	/** @var IL10N */
	private $l10n;
	/** @var Defaults */
	private $defaults;
	/** @var IConfig */
	private $config;

	/**
	 * @param IL10N $l10n
	 * @param Defaults $defaults
	 * @param IConfig $config
	 */
	public function __construct(IL10N $l10n,
								Defaults $defaults,
								IConfig $config) {
		$this->l10n = $l10n;
		$this->defaults = $defaults;
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$providerIds = explode(',', $this->config->getAppValue('user_saml', 'providerIds', '1'));
		natsort($providerIds);
		$providers = [];
		foreach ($providerIds as $id) {
			$prefix = $id === '1' ? '' : $id .'-';
			$name = $this->config->getAppValue('user_saml', $prefix . 'general-idp0_display_name', '');
			$providers[] = [
				'id' => $id,
				'name' => $name === '' ? $this->l10n->t('Provider ') . $id : $name
				];
		}
		$serviceProviderFields = [
			'x509cert' => $this->l10n->t('X.509 certificate of the Service Provider'),
			'privateKey' => $this->l10n->t('Private key of the Service Provider'),
		];
		$securityOfferFields = [
			'nameIdEncrypted' => $this->l10n->t('Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted.'),
			'authnRequestsSigned' => $this->l10n->t('Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]'),
			'logoutRequestSigned' => $this->l10n->t('Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed.'),
			'logoutResponseSigned' => $this->l10n->t('Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed.'),
			'signMetadata' => $this->l10n->t('Whether the metadata should be signed.'),
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
			'lowercaseUrlencoding' =>  $this->l10n->t('ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification.'),
		];
		$generalSettings = [
			'idp0_display_name' => [
				'text' => $this->l10n->t('Optional display name of the identity provider (default: "SSO & SAML log in")'),
				'type' => 'line',
				'required' => false,
			],
			'uid_mapping' => [
				'text' => $this->l10n->t('Attribute to map the UID to.'),
				'type' => 'line',
				'required' => true,
			],
			'require_provisioned_account' => [
				'text' => $this->l10n->t('Only allow authentication if an account exists on some other backend. (e.g. LDAP)'),
				'type' => 'checkbox',
				'global' => true,
			],
			'allow_multiple_user_back_ends' => [
				'text' => $this->l10n->t('Allow the use of multiple user back-ends (e.g. LDAP)'),
				'type' => 'checkbox',
				'hideForEnv' => true,
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
			'group_mapping' => [
				'text' => $this->l10n->t('Attribute to map the users groups to.'),
				'type' => 'line',
				'required' => true,
			],
		];

		$type = $this->config->getAppValue('user_saml', 'type');
		if($type === 'saml') {
			$generalSettings['use_saml_auth_for_desktop'] = [
				'text' => $this->l10n->t('Use SAML auth for the %s desktop clients (requires user re-authentication)', [$this->defaults->getName()]),
				'type' => 'checkbox',
				'global' => true,
			];
		}

		$params = [
			'sp' => $serviceProviderFields,
			'security-offer' => $securityOfferFields,
			'security-required' => $securityRequiredFields,
			'security-general' => $securityGeneral,
			'general' => $generalSettings,
			'attribute-mapping' => $attributeMappingSettings,
			'type' => $type,
			'providers' => $providers
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
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority() {
		return 0;
	}

}
