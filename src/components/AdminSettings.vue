<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection
		:name="t('user_saml', 'SSO & SAML authentication')"
		:description="t('user_saml', 'Single sign-on and SAML authentication settings')"
		:data-type="type"
		docUrl="https://portal.nextcloud.com/article/configuring-single-sign-on-10.html">
		<!-- Warning: admin user -->
		<NcNoteCard v-if="type !== ''" type="warning">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<span v-html="adminWarningText" />
		</NcNoteCard>

		<!-- Step 1: Choose type -->
		<div v-if="type === ''" class="choose-type">
			<p>
				{{ t('user_saml', 'Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable.') }}
			</p>
			<div class="choose-type__buttons">
				<NcButton variant="primary" @click="chooseSaml">
					{{ t('user_saml', 'Use built-in SAML authentication') }}
				</NcButton>
				<NcButton variant="secondary" @click="chooseEnv">
					{{ t('user_saml', 'Use environment variable') }}
				</NcButton>
			</div>
		</div>

		<!-- Global settings (shown when type is set) -->
		<div v-if="type !== ''" class="global-settings">
			<h3>{{ t('user_saml', 'Global settings') }}</h3>
			<template v-for="(attribute, key) in generalSettings" :key="key">
				<div v-if="attribute.provider_type === '' || attribute.provider_type === type">
					<div v-if="attribute.type === 'checkbox' && attribute.global">
						<NcCheckboxRadioSwitch
							:modelValue="globalConfig[key] === '1'"
							@update:modelValue="(val) => onGlobalCheckboxChange(key, val)">
							{{ attribute.text }}
						</NcCheckboxRadioSwitch>
					</div>
					<div v-else-if="attribute.type === 'line' && attribute.global !== undefined">
						<NcInputField
							v-model="globalConfig[key]"
							:label="attribute.text"
							:required="attribute.required"
							@update:modelValue="onGlobalInputChange(key, globalConfig[key])" />
					</div>
				</div>
			</template>
		</div>

		<!-- Provider list (saml mode only) -->
		<div v-if="type === 'saml'" class="provider-list">
			<h3>{{ t('user_saml', 'Identity providers') }}</h3>
			<ul class="provider-list__items">
				<li
					v-for="provider in providers"
					:key="provider.id"
					class="provider-list__item">
					<!-- Provider name / configure button -->
					<NcButton
						class="provider-list__item-btn"
						:variant="currentProviderId === provider.id ? 'primary' : 'secondary'"
						@click="openProviderDialog(provider)">
						{{ provider.name }}
					</NcButton>
					<!-- Per-provider delete button -->
					<NcButton
						v-if="providers.length > 1"
						variant="error"
						:aria-label="t('user_saml', 'Remove {name}', { name: provider.name })"
						@click="removeProvider(provider.id)">
						<template #icon>
							<IconDelete :size="20" />
						</template>
					</NcButton>
				</li>
			</ul>
			<NcButton variant="primary" @click="addProvider">
				<template #icon>
					<IconPlus :size="20" />
				</template>
				{{ t('user_saml', 'Add identity provider') }}
			</NcButton>
		</div>

		<!-- Environment-variable mode: per-provider general settings inline -->
		<div v-if="type === 'environment-variable' && providers.length > 0" class="env-var-settings">
			<h3>{{ t('user_saml', 'Environment variable provider settings') }}</h3>
			<ProviderGeneralSection
				v-model="envVarGeneralConfig"
				:generalSettings="generalSettings"
				@fieldChange="onEnvVarFieldChange" />
		</div>

		<!-- Actions row (reset) -->
		<div v-if="type !== ''" class="settings-actions">
			<NcButton variant="warning" @click="resetSettings">
				{{ t('user_saml', 'Reset settings') }}
			</NcButton>
		</div>

		<!-- Per-provider settings dialog (SAML mode only) -->
		<ProviderSettingsDialog
			v-if="type === 'saml' && dialogProvider !== null"
			:open="dialogOpen"
			:provider="dialogProvider"
			:generalSettings="generalSettings"
			:spSettings="spSettings"
			:nameIdFormats="nameIdFormats"
			:attributeMappingSettings="attributeMappingSettings"
			:securityOffer="securityOffer"
			:securityRequired="securityRequired"
			:securityGeneral="securityGeneral"
			:userFilterSettings="userFilterSettings"
			:showAttributeMapping="showAttributeMapping"
			@update:open="dialogOpen = $event"
			@providerNameChanged="onProviderNameChanged"
			@close="dialogOpen = false" />
	</NcSettingsSection>
</template>

<script setup lang="ts">
import type {
	GlobalConfig,
	NameIdFormatsMap,
	Provider,
	SecurityGeneralMap,
	SecurityMap,
	SettingsMap,
} from '../types.ts'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import ProviderGeneralSection from './ProviderGeneralSection.vue'
import ProviderSettingsDialog from './ProviderSettingsDialog.vue'
import logger from '../logger.ts'

const props = withDefaults(defineProps<{
	initialType: string
	initialProviders: Provider[]
	generalSettings: SettingsMap
	spSettings: SettingsMap
	nameIdFormats: NameIdFormatsMap
	attributeMappingSettings: SettingsMap
	securityOffer: SecurityMap
	securityRequired: SecurityMap
	securityGeneral: SecurityGeneralMap
	userFilterSettings: SettingsMap
	/** Global config values stored in oc_appconfig (not per-provider) */
	initialGlobalConfig: GlobalConfig
}>(), {
	initialType: '',
	initialProviders: () => [],
	generalSettings: () => ({}),
	spSettings: () => ({}),
	nameIdFormats: () => ({}),
	attributeMappingSettings: () => ({}),
	securityOffer: () => ({}),
	securityRequired: () => ({}),
	securityGeneral: () => ({}),
	userFilterSettings: () => ({}),
	initialGlobalConfig: () => ({}),
})

const type = ref<string>(props.initialType)
const providers = ref<Provider[]>([...props.initialProviders])
const currentProviderId = ref<Provider['id'] | null>(props.initialProviders[0]?.id ?? null)

/** Global config stored in oc_appconfig, shared across all providers */
const globalConfig = ref<GlobalConfig>({ ...props.initialGlobalConfig })

/** Dialog state (SAML mode only) */
const dialogOpen = ref<boolean>(false)
const dialogProvider = ref<Provider | null>(null)

/** General config for the single env-var provider, loaded on mount */
const envVarGeneralConfig = ref<Record<string, string>>({})

const showAttributeMapping = computed(() => globalConfig.value.require_provisioned_account !== '1')

const adminWarningText = computed(() => {
	const loginUrl = generateUrl('/login') + '?direct=1'
	const link = `<a href="${loginUrl}">${loginUrl}</a>`
	return t(
		'user_saml',
		'Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular account will not be possible anymore, unless you go directly to the URL {url}.',
		{ url: link },
		undefined,
		{ escape: false },
	)
})

onMounted(async () => {
	if (type.value === 'environment-variable') {
		await ensureEnvVarProvider()
		await loadEnvVarConfig()
	}
})

/**
 *
 */
async function ensureEnvVarProvider(): Promise<void> {
	if (providers.value.length > 0) { return }
	try {
		const { data } = await axios.post(generateUrl('/apps/user_saml/settings/providerSettings'))
		providers.value.push({ id: data.id, name: t('user_saml', 'Provider {id}', { id: data.id }) })
	} catch (error) {
		logger.error('Could not create implicit provider for environment-variable mode', { error })
		showError(t('user_saml', 'Could not create provider configuration'))
	}
}

/**
 *
 */
async function loadEnvVarConfig(): Promise<void> {
	const provider = providers.value[0]
	if (!provider) { return }
	try {
		const { data } = await axios.get(generateUrl(`/apps/user_saml/settings/providerSettings/${provider.id}`))
		envVarGeneralConfig.value = data.general ?? {}
	} catch (error) {
		logger.error('Could not load provider settings', { error })
		showError(t('user_saml', 'Could not load provider settings'))
	}
}

/**
 *
 * @param key
 * @param value
 */
async function onEnvVarFieldChange(key: string, value: string): Promise<void> {
	const provider = providers.value[0]
	if (!provider) { return }
	try {
		await axios.put(
			generateUrl(`/apps/user_saml/settings/providerSettings/${provider.id}`),
			{ configKey: `general-${key}`, configValue: value.trim() },
		)
		showSuccess(t('user_saml', 'Saved'))
	} catch (error) {
		logger.error('Could not save provider setting', { error })
		showError(t('user_saml', 'Could not save configuration'))
	}
}

/**
 *
 * @param provider
 */
function openProviderDialog(provider: Provider): void {
	currentProviderId.value = provider.id
	dialogProvider.value = provider
	dialogOpen.value = true
}

/**
 *
 * @param root0
 * @param root0.id
 * @param root0.name
 */
function onProviderNameChanged({ id, name }: { id: Provider['id'], name: string }): void {
	const provider = providers.value.find((p) => p.id === id)
	if (provider) {
		provider.name = name
		// Keep dialogProvider in sync so the dialog title updates immediately
		if (dialogProvider.value?.id === id) {
			dialogProvider.value = { ...dialogProvider.value, name }
		}
	}
}

/**
 *
 * @param key
 * @param value
 */
async function updateAppConfig(key: string, value: string): Promise<void> {
	await confirmPassword()

	const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
		appId: 'user_saml',
		key,
	})

	try {
		const { data } = await axios.post(url, { value })
		if (data.ocs.meta.status !== 'ok') {
			const message = data.ocs.meta.message
				?? t('user_saml', 'Unknown error (status {code})', { code: data.ocs.meta.statuscode })
			showError(message)
			logger.error('Error updating user_saml appconfig', { error: data.ocs })
			throw new Error(message)
		}
	} catch (error) {
		logger.error('Error updating user_saml appconfig', { error })
		showError(t('user_saml', 'Unable to update configuration'))
		throw error
	}
}

/**
 *
 */
async function chooseSaml() {
	await updateAppConfig('type', 'saml')
	type.value = 'saml'
}

/**
 *
 */
async function chooseEnv(): Promise<void> {
	await updateAppConfig('type', 'environment-variable')
	type.value = 'environment-variable'
	await ensureEnvVarProvider()
	await loadEnvVarConfig()
}

/**
 *
 */
async function resetSettings() {
	await updateAppConfig('type', '')
	type.value = ''
}

/**
 *
 */
async function addProvider() {
	try {
		const { data } = await axios.post(generateUrl('/apps/user_saml/settings/providerSettings'))
		const newProvider = { id: data.id, name: t('user_saml', 'Provider {id}', { id: data.id }) }
		providers.value.push(newProvider)
		openProviderDialog(newProvider)
	} catch (error) {
		logger.error('Could not add provider', { error })
		showError(t('user_saml', 'Could not add identity provider'))
	}
}

/**
 *
 * @param providerId
 */
async function removeProvider(providerId: Provider['id']): Promise<void> {
	if (providers.value.length <= 1) { return }
	try {
		await axios.delete(generateUrl(`/apps/user_saml/settings/providerSettings/${providerId}`))
		providers.value = providers.value.filter((p) => p.id !== providerId)
		// If we just removed the currently open provider, close the dialog
		if (dialogProvider.value?.id === providerId) {
			dialogOpen.value = false
			dialogProvider.value = null
		}
		currentProviderId.value = providers.value[0].id
	} catch (error) {
		logger.error('Could not remove provider', { error })
		showError(t('user_saml', 'Could not remove identity provider'))
	}
}

/**
 *
 * @param key
 * @param checked
 */
async function onGlobalCheckboxChange(key: string, checked: boolean): Promise<void> {
	const value = checked ? '1' : '0'
	try {
		await updateAppConfig(`general-${key}`, value)
		globalConfig.value[key] = value
		showSuccess(t('user_saml', 'Saved'))
	} catch {
		// updateAppConfig already called showError
	}
}

/**
 *
 * @param key
 * @param value
 */
async function onGlobalInputChange(key: string, value: string): Promise<void> {
	try {
		await updateAppConfig(`general-${key}`, value.trim())
		showSuccess(t('user_saml', 'Saved'))
	} catch {
		// updateAppConfig already called showError
	}
}
</script>

<style scoped>
.choose-type__buttons {
	display: flex;
	flex-direction: row;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
	flex-wrap: wrap;
	margin-block-start: calc(var(--default-grid-baseline, 4px) * 2);
}

.global-settings,
.provider-list,
.env-var-settings {
	margin-block-start: calc(var(--default-grid-baseline, 4px) * 4);
}

.provider-list__items {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 1);
	list-style: none;
	padding: 0;
	margin-block-end: calc(var(--default-grid-baseline, 4px) * 2);
}

.provider-list__item {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: calc(var(--default-grid-baseline, 4px) * 1);
}

.provider-list__item-btn {
	flex: 1;
	justify-content: flex-start;
}

.settings-actions {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
	flex-wrap: wrap;
	margin-block-start: calc(var(--default-grid-baseline, 4px) * 4);
}
</style>
