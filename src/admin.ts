/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { GlobalConfig, NameIdFormatsMap, Provider, SecurityGeneralMap, SecurityMap, SettingsMap } from './types.ts'

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

const app = createApp(AdminSettings, {
	initialType: loadState<string>('user_saml', 'type', ''),
	initialProviders: loadState<Provider[]>('user_saml', 'providers', []),
	generalSettings: loadState<SettingsMap>('user_saml', 'generalSettings', {}),
	spSettings: loadState<SettingsMap>('user_saml', 'spSettings', {}),
	nameIdFormats: loadState<NameIdFormatsMap>('user_saml', 'nameIdFormats', {}),
	attributeMappingSettings: loadState<SettingsMap>('user_saml', 'attributeMappingSettings', {}),
	securityOffer: loadState<SecurityMap>('user_saml', 'securityOffer', {}),
	securityRequired: loadState<SecurityMap>('user_saml', 'securityRequired', {}),
	securityGeneral: loadState<SecurityGeneralMap>('user_saml', 'securityGeneral', {}),
	userFilterSettings: loadState<SettingsMap>('user_saml', 'userFilterSettings', {}),
	initialGlobalConfig: loadState<GlobalConfig>('user_saml', 'globalConfig', {}),
})

app.mount('#user-saml-vue')
