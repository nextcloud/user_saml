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

namespace OCA\User_SAML;

use InvalidArgumentException;
use OCA\User_SAML\Db\ConfigurationsMapper;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OneLogin\Saml2\Constants;

class SAMLSettings {
	private const LOADED_NONE = 0;
	private const LOADED_CHOSEN = 1;
	private const LOADED_ALL = 2;

	public const IDP_CONFIG_KEYS = [
		'general-idp0_display_name',
		'general-uid_mapping',
		'idp-entityId',
		'idp-singleLogoutService.responseUrl',
		'idp-singleLogoutService.url',
		'idp-singleSignOnService.url',
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
		'sp-x509cert',
		'sp-name-id-format',
		'sp-privateKey',
	];

	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;
	/** @var ISession */
	private $session;
	/** @var array list of global settings which are valid for every idp */
	private $globalSettings = ['general-require_provisioned_account', 'general-allow_multiple_user_back_ends', 'general-use_saml_auth_for_desktop'];
	/** @var array<int, array<string, string>> */
	private $configurations;
	/** @var int */
	private $configurationsLoadedState = self::LOADED_NONE;
	/** @var ConfigurationsMapper */
	private $mapper;

	/**
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param ISession $session
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		IConfig       $config,
		ISession      $session,
		ConfigurationsMapper $mapper
	) {
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->session = $session;
		$this->mapper = $mapper;
	}

	/**
	 * get list of the configured IDPs
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
	 * check if multiple user back ends are allowed
	 */
	public function allowMultipleUserBackEnds(): bool {
		$type = $this->config->getAppValue('user_saml', 'type');
		$setting = $this->config->getAppValue('user_saml', 'general-allow_multiple_user_back_ends', '0');
		return ($setting === '1' && $type === 'saml');
	}

	public function usesSloWebServerDecode(): bool {
		return $this->config->getAppValue('user_saml', 'security-sloWebServerDecode', '0') === '1';
	}

	/**
	 * get config for given IDP
	 *
	 * @throws Exception
	 */
	public function getOneLoginSettingsArray(int $idp): array {
		$this->ensureConfigurationsLoaded($idp);

		$settings = [
			'strict' => true,
			'debug' => $this->config->getSystemValue('debug', false),
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
			],
			'sp' => [
				'entityId' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.getMetadata'),
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
		foreach (array_keys($settings) as $configKey) {
			if (!in_array($configKey, self::IDP_CONFIG_KEYS)) {
				throw new InvalidArgumentException('Invalid config key');
			}
		}

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
		if (self::LOADED_ALL === $this->configurationsLoadedState
			|| (self::LOADED_CHOSEN === $this->configurationsLoadedState
				&& isset($this->configurations[$idp])
			)
		) {
			return;
		}

		if ($idp !== -1)  {
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
