<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Controller;

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {

	/** @var IConfig */
	private $config;
	/** @var Admin */
	private $admin;
	/** @var SAMLSettings */
	private $samlSettings;

	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		Admin $admin,
		SAMLSettings $samlSettings
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->admin = $admin;
		$this->samlSettings = $samlSettings;
	}

	public function getSamlProviderIds(): DataResponse {
		$keys = array_keys($this->samlSettings->getListOfIdps());
		return new DataResponse([ 'providerIds' => implode(',', $keys)]);
	}

	/**
	 * @param $providerId
	 * @return array of categories containing entries for each config parameter with their value
	 */
	public function getSamlProviderSettings(int $providerId) {
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
		];
		/* Fetch all config values for the given providerId */
		$settings = [];
		$storedSettings = $this->samlSettings->get($providerId);
		foreach ($params as $category => $content) {
			if (empty($content) || $category === 'providers' || $category === 'type') {
				continue;
			}
			foreach ($content as $setting => $details) {
				/* use security as category instead of security-* */
				if (strpos($category, 'security-') === 0) {
					$category = 'security';
				}
				// make sure we properly fetch the attribute mapping
				// as this is the only category that has the saml- prefix on config keys
				if (strpos($category, 'attribute-mapping') === 0) {
					$category = 'attribute-mapping';
					$key = 'saml-attribute-mapping' . '-' . $setting;
				} else {
					$key = $category . '-' . $setting;
				}

				if (isset ($details['global']) && $details['global']) {
					$settings[$category][$setting] = $this->config->getAppValue('user_saml', $key, '');
				} else {
					$settings[$category][$setting] = $storedSettings[$key] ?? '';
				}
			}
		}
		return $settings;
	}

	public function deleteSamlProviderSettings($providerId) {
		$this->samlSettings->delete($providerId);
		return new Response();
	}

	public function setProviderSetting(int $providerId, string $configKey, string $configValue) {
		$configuration = $this->samlSettings->get($providerId);
		$configuration[$configKey] = $configValue;
		$this->samlSettings->set($providerId, $configuration);
		return new Response();
	}

	public function newSamlProviderSettingsId() {
		return new DataResponse(['id' => $this->samlSettings->getNewProviderId()]);
	}
}
