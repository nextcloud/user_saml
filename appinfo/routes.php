<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AppInfo;

return [
	'routes' => [
		[
			'name' => 'SAML#login',
			'url' => '/saml/login',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#base',
			'url' => '/saml',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#getMetadata',
			'url' => '/saml/metadata',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#assertionConsumerService',
			'url' => '/saml/acs',
			'verb' => 'POST',
		],
		[
			'name' => 'SAML#singleLogoutService',
			'url' => '/saml/sls',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#singleLogoutService',
			'url' => '/saml/sls',
			'verb' => 'POST',
			'postfix' => 'slspost',
		],
		[
			'name' => 'SAML#notPermitted',
			'url' => '/saml/notPermitted',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#notProvisioned',
			'url' => '/saml/notProvisioned',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#genericError',
			'url' => '/saml/error',
			'verb' => 'GET',
		],
		[
			'name' => 'SAML#selectUserBackEnd',
			'url' => '/saml/selectUserBackEnd',
			'verb' => 'GET',
		],
		[
			'name' => 'Settings#getSamlProviderIds',
			'url' => '/settings/providers',
			'verb' => 'GET',
		],
		[
			'name' => 'Settings#getSamlProviderSettings',
			'url' => '/settings/providerSettings/{providerId}',
			'verb' => 'GET',
			'defaults' => [
				'providerId' => 1
			],
			'requirements' => [
				'providerId' => '\d+'
			]
		],
		[
			'name' => 'Settings#setProviderSetting',
			'url' => '/settings/providerSettings/{providerId}',
			'verb' => 'PUT',
			'defaults' => [
				'providerId' => 1
			],
			'requirements' => [
				'providerId' => '\d+'
			]
		],
		[
			'name' => 'Settings#newSamlProviderSettingsId',
			'url' => '/settings/providerSettings',
			'verb' => 'POST',
		],
		[
			'name' => 'Settings#deleteSamlProviderSettings',
			'url' => '/settings/providerSettings/{providerId}',
			'verb' => 'DELETE',
			'defaults' => [
				'providerId' => 1
			],
			'requirements' => [
				'providerId' => '\d+'
			]
		],
		[
			'name' => 'Timezone#setTimezone',
			'url' => '/config/timezone',
			'verb' => 'POST',
		],
	],
];
