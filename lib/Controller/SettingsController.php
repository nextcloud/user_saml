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

use OCA\User_SAML\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {

	/** @var IConfig */
	private $config;
	/** @var Admin */
	private $admin;

	public function __construct($appName,
								IRequest $request,
								IConfig $config,
								Admin $admin) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->admin = $admin;
	}

	/**
	 * @param $providerId
	 * @return array of categories containing entries for each config parameter with their value
	 */
	public function getSamlProviderSettings($providerId) {
		/**
		 * This uses the list of available config parameters from the admin section
		 * and extends it with fields that are not coming from \OCA\User_SAML\Settings\Admin
		 */
		$params = $this->admin->getForm()->getParams();
		$params['idp'] = [
			'singleLogoutService.url' => null,
			'singleSignOnService.url' => null,
			'idp-entityId' => null,
		];
		/* Fetch all config values for the given providerId */
		$settings = [];
		foreach ($params as $category => $content) {
			if (empty($content) || $category === 'providers') {
				continue;
			}
			foreach ($content as $setting => $details) {
				$prefix = $providerId === '1' ? '' : $providerId . '-';
				$key = $prefix . $category . '-' . $setting;
				/* use security as category instead of security-* */
				if (strpos($category, 'security-') === 0) {
					$category = 'security';
				}
				$settings[$category][$setting] = $this->config->getAppValue('user_saml', $key, '');
			}
		}
		return $settings;
	}

}
