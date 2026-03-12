/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { loadState } from '@nextcloud/initial-state'
import AdminSettings from './components/AdminSettings.vue'

const app = createApp(AdminSettings, {
	initialType: loadState('user_saml', 'type', ''),
	initialProviders: loadState('user_saml', 'providers', []),
	generalSettings: loadState('user_saml', 'generalSettings', {}),
	spSettings: loadState('user_saml', 'spSettings', {}),
	nameIdFormats: loadState('user_saml', 'nameIdFormats', {}),
	attributeMappingSettings: loadState('user_saml', 'attributeMappingSettings', {}),
	securityOffer: loadState('user_saml', 'securityOffer', {}),
	securityRequired: loadState('user_saml', 'securityRequired', {}),
	securityGeneral: loadState('user_saml', 'securityGeneral', {}),
	userFilterSettings: loadState('user_saml', 'userFilterSettings', {}),
	initialGlobalConfig: loadState('user_saml', 'globalConfig', {}),
})

app.mount('#user-saml-vue')
