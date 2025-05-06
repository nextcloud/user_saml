<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Controller;

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use OneLogin\Saml2\Constants;

class SettingsController extends Controller {

	public function __construct(
		$appName,
		IRequest $request,
		private readonly IConfig $config,
		private readonly Admin $admin,
		private readonly SAMLSettings $samlSettings,
	) {
		parent::__construct($appName, $request);
	}

	public function getSamlProviderIds(): DataResponse {
		$keys = array_keys($this->samlSettings->getListOfIdps());
		return new DataResponse([ 'providerIds' => implode(',', $keys)]);
	}

	/**
	 * @return array of categories containing entries for each config parameter with their value
	 */
	public function getSamlProviderSettings(int $providerId): array {
		/**
		 * This uses the list of available config parameters from the admin section
		 * and extends it with fields that are not coming from \OCA\User_SAML\Settings\Admin
		 */
		$params = $this->admin->getForm()->getParams();
		$params['idp'] = [
			'singleLogoutService.url' => ['required' => false],
			'singleLogoutService.responseUrl' => ['required' => false],
			'singleSignOnService.url' => ['required' => false],
			'entityId' => ['required' => false],
			'x509cert' => ['required' => false],
			'passthroughParameters' => ['required' => false],
		];
		/* Fetch all config values for the given providerId */

		// initialize settings with default value for option box (others are left empty)
		$settings['sp']['name-id-format'] = Constants::NAMEID_UNSPECIFIED;
		$storedSettings = $this->samlSettings->get($providerId);
		foreach ($params as $category => $content) {
			if (empty($content) || $category === 'providers' || $category === 'type') {
				continue;
			}
			foreach ($content as $setting => $details) {
				/* use security as category instead of security-* */
				if (str_starts_with($category, 'security-')) {
					$category = 'security';
				}
				// make sure we properly fetch the attribute mapping
				// as this is the only category that has the saml- prefix on config keys
				if (str_starts_with($category, 'attribute-mapping')) {
					$category = 'attribute-mapping';
					$key = 'saml-attribute-mapping' . '-' . $setting;
				} elseif ($category === 'name-id-formats') {
					if ($setting === $storedSettings['sp-name-id-format']) {
						$settings['sp']['name-id-format'] = $storedSettings['sp-name-id-format'];
						//continue 2;
					}
					continue;
				} else {
					$key = $category . '-' . $setting;
				}

				if (isset($details['global']) && $details['global']) {
					// Read legacy data from oc_appconfig
					$settings[$category][$setting] = $this->config->getAppValue('user_saml', $key, '');
				} else {
					$settings[$category][$setting] = $storedSettings[$key] ?? '';
				}
			}
		}
		return $settings;
	}

	public function deleteSamlProviderSettings($providerId): Response {
		$this->samlSettings->delete($providerId);
		return new Response();
	}

	public function setProviderSetting(int $providerId, string $configKey, string $configValue): Response {
		$configuration = $this->samlSettings->get($providerId);
		$configuration[$configKey] = $configValue;
		$this->samlSettings->set($providerId, $configuration);
		return new Response();
	}

	public function newSamlProviderSettingsId(): DataResponse {
		return new DataResponse(['id' => $this->samlSettings->getNewProviderId()]);
	}
}
