<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use InvalidArgumentException;
use OCA\User_SAML\Db\ConfigurationsMapper;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\ISession;
use OCP\IURLGenerator;
use OneLogin\Saml2\Constants;

class SAMLSettings {
	private const LOADED_NONE = 0;
	private const LOADED_CHOSEN = 1;
	private const LOADED_ALL = 2;

	// list of global settings which are valid for every idp:
	// 'general-require_provisioned_account', 'general-allow_multiple_user_back_ends'

	// IdP-specific keys
	public const IDP_CONFIG_KEYS = [
		'general-idp0_display_name',
		'general-uid_mapping',
		'general-is_saml_request_using_post',
		'general-saml_request_method',
		'idp-entityId',
		'idp-singleLogoutService.responseUrl',
		'idp-singleLogoutService.url',
		'idp-singleSignOnService.url',
		'idp-passthroughParameters',
		'idp-x509cert',
		'security-authnRequestsSigned',
		'security-general',
		'security-logoutRequestSigned',
		'security-logoutResponseSigned',
		'security-lowercaseUrlencoding',
		'security-nameIdEncrypted',
		'security-offer',
		'security-required',
		'security-signatureAlgorithm',
		'security-signMetadata',
		'security-sloWebServerDecode',
		'security-wantAssertionsEncrypted',
		'security-wantAssertionsSigned',
		'security-wantMessagesSigned',
		'security-wantNameId',
		'security-wantNameIdEncrypted',
		'security-wantXMLValidation',
		'saml-attribute-mapping-displayName_mapping',
		'saml-attribute-mapping-email_mapping',
		'saml-attribute-mapping-group_mapping',
		'saml-attribute-mapping-home_mapping',
		'saml-attribute-mapping-quota_mapping',
		'saml-attribute-mapping-mfa_mapping',
		'saml-attribute-mapping-group_mapping_prefix',
		'saml-user-filter-reject_groups',
		'saml-user-filter-require_groups',
		'sp-entityId',
		'sp-x509cert',
		'sp-name-id-format',
		'sp-privateKey',
	];

	public const DEFAULT_GROUP_PREFIX = 'SAML_';

	/** @var array<int, array<string, string>> */
	private $configurations = [];
	/** @var int */
	private $configurationsLoadedState = self::LOADED_NONE;

	public function __construct(
		private readonly IURLGenerator $urlGenerator,
		private readonly IConfig $config,
		private readonly ISession $session,
		private readonly ConfigurationsMapper $mapper,
	) {
	}

	/**
	 * Get list of the configured IDPs
	 *
	 * @return array<int, string>
	 * @throws Exception
	 */
	public function getListOfIdps(): array {
		$this->ensureConfigurationsLoaded();

		$result = [];
		foreach ($this->configurations as $configID => $config) {
			// no fancy array_* method, because there might be thousands
			$result[$configID] = $config['general-idp0_display_name'] ?? '';
		}

		return $result;
	}

	/**
	 * Check if multiple user back ends are allowed
	 */
	public function allowMultipleUserBackEnds(): bool {
		$type = $this->config->getAppValue('user_saml', 'type');
		$setting = $this->config->getAppValue('user_saml', 'general-allow_multiple_user_back_ends', '0');
		return ($setting === '1' && $type === 'saml');
	}

	public function usesSloWebServerDecode(int $idp): bool {
		$config = $this->get($idp);
		return ($config['security-sloWebServerDecode'] ?? false) === '1';
	}

	/**
	 * Get config for given IDP
	 *
	 * @throws Exception
	 */
	public function getOneLoginSettingsArray(int $idp): array {
		$this->ensureConfigurationsLoaded($idp);

		$settings = [
			'strict' => true,
			'debug' => $this->config->getSystemValueBool('debug', false),
			'baseurl' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.base'),
			'security' => [
				'nameIdEncrypted' => ($this->configurations[$idp]['security-nameIdEncrypted'] ?? '0') === '1',
				'authnRequestsSigned' => ($this->configurations[$idp]['security-authnRequestsSigned'] ?? '0') === '1',
				'logoutRequestSigned' => ($this->configurations[$idp]['security-logoutRequestSigned'] ?? '0') === '1',
				'logoutResponseSigned' => ($this->configurations[$idp]['security-logoutResponseSigned'] ?? '0') === '1',
				'signMetadata' => ($this->configurations[$idp]['security-signMetadata'] ?? '0') === '1',
				'wantMessagesSigned' => ($this->configurations[$idp]['security-wantMessagesSigned'] ?? '0') === '1',
				'wantAssertionsSigned' => ($this->configurations[$idp]['security-wantAssertionsSigned'] ?? '0') === '1',
				'wantAssertionsEncrypted' => ($this->configurations[$idp]['security-wantAssertionsEncrypted'] ?? '0') === '1',
				'wantNameId' => ($this->configurations[$idp]['security-wantNameId'] ?? '0') === '1',
				'wantNameIdEncrypted' => ($this->configurations[$idp]['security-wantNameIdEncrypted'] ?? '0') === '1',
				'wantXMLValidation' => ($this->configurations[$idp]['security-wantXMLValidation'] ?? '0') === '1',
				'requestedAuthnContext' => false,
				'lowercaseUrlencoding' => ($this->configurations[$idp]['security-lowercaseUrlencoding'] ?? '0') === '1',
				'signatureAlgorithm' => $this->configurations[$idp]['security-signatureAlgorithm'] ?? null,
				// "sloWebServerDecode" is not expected to be passed to the OneLogin class
			],
			'sp' => [
				'entityId' => (array_key_exists('sp-entityId', $this->configurations[$idp]) && trim($this->configurations[$idp]['sp-entityId']) != '')
					? $this->configurations[$idp]['sp-entityId']
					: $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.getMetadata'),
				'assertionConsumerService' => [
					'url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.assertionConsumerService'),
				],
				'NameIDFormat' => $this->configurations[$idp]['sp-name-id-format'] ?? Constants::NAMEID_UNSPECIFIED,
				'x509cert' => $this->configurations[$idp]['sp-x509cert'] ?? '',
				'privateKey' => $this->configurations[$idp]['sp-privateKey'] ?? '',
			],
			'idp' => [
				'entityId' => $this->configurations[$idp]['idp-entityId'] ?? '',
				'singleSignOnService' => [
					'url' => $this->configurations[$idp]['idp-singleSignOnService.url'] ?? '',
				],
				'x509cert' => $this->configurations[$idp]['idp-x509cert'] ?? '',
				'passthroughParameters' => $this->configurations[$idp]['idp-passthroughParameters'] ?? '',
			],
		];

		// must be added only if configured
		if (($this->configurations[$idp]['idp-singleLogoutService.url'] ?? '') !== '') {
			$settings['idp']['singleLogoutService'] = ['url' => $this->configurations[$idp]['idp-singleLogoutService.url']];
			$settings['sp']['singleLogoutService'] = ['url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.singleLogoutService')];
			if (($this->configurations[$idp]['idp-singleLogoutService.responseUrl'] ?? '') !== '') {
				$settings['idp']['singleLogoutService']['responseUrl'] = $this->configurations[$idp]['idp-singleLogoutService.responseUrl'];
			}
		}

		return $settings;
	}

	public function getProviderId(): int {
		// defaults to 1, needed for env-mode
		return (int)($this->session->get('user_saml.Idp') ?? 1);
	}

	public function getNewProviderId(): int {
		return $this->mapper->reserveId();
	}

	/**
	 * @throws Exception
	 */
	public function get(int $id): array {
		$this->ensureConfigurationsLoaded($id);
		return $this->configurations[$id] ?? [];
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function set(int $id, array $settings): void {
		$settings = array_filter($settings, static function (string $configKey): bool {
			return in_array($configKey, self::IDP_CONFIG_KEYS, true);
		}, ARRAY_FILTER_USE_KEY);

		$this->mapper->set($id, $settings);
	}

	/**
	 * @throws Exception
	 */
	public function delete(int $id): void {
		$this->mapper->deleteById($id);
	}

	/**
	 * @throws Exception
	 */
	protected function ensureConfigurationsLoaded(int $idp = -1): void {
		if ($this->configurationsLoadedState === self::LOADED_ALL
			|| ($this->configurationsLoadedState === self::LOADED_CHOSEN
				&& isset($this->configurations[$idp])
			)
		) {
			return;
		}

		if ($idp !== -1) {
			$this->configurations[$idp] = $this->mapper->get($idp);
		} else {
			$configs = $this->mapper->getAll();
			foreach ($configs as $id => $config) {
				$this->configurations[$id] = $config;
			}
		}

		$this->configurationsLoadedState = $idp === -1 ? self::LOADED_ALL : self::LOADED_CHOSEN;
	}
}
