<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		:open="open"
		:name="t('user_saml', 'Configure: {name}', { name: provider.name })"
		:closeOnClickOutside="false"
		isForm
		size="large"
		@update:open="onOpenChanged"
		@submit="saveAll">
		<template #actions>
			<NcButton
				variant="secondary"
				:href="metadataUrl"
				download>
				{{ t('user_saml', 'Download metadata XML') }}
			</NcButton>
			<NcButton variant="tertiary" :disabled="!isDirty" @click="cancelChanges">
				{{ t('user_saml', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" type="submit" :disabled="!isDirty || isSaving">
				{{ isSaving ? t('user_saml', 'Saving…') : t('user_saml', 'Edit') }}
			</NcButton>
		</template>

		<div class="provider-settings" v-if="!isLoading">
			<!-- General (per-provider) -->
			<NcSettingsSection :name="t('user_saml', 'General')" :level="3">
				<ProviderGeneralSection
					:generalSettings="generalSettings"
					:modelValue="draft.general ?? {}"
					type="saml"
					@update:modelValue="(val) => { draft.general = val }"
					@fieldChange="(key, value) => setDraft('general', key, value)" />
			</NcSettingsSection>

			<!-- Service Provider Data -->
			<NcSettingsSection
				:name="t('user_saml', 'Service Provider Data')"
				:level="3"
				:description="t('user_saml', 'If your Service Provider should use certificates you can optionally specify them here.')">
				<NcSelect
					v-model="nameIdFormatModel"
					:inputLabel="t('user_saml', 'Name ID format')"
					:options="nameIdFormatOptions"
					:clearable="false" />

				<template v-for="(attribute, key) in spSettings" :key="key">
					<NcTextArea
						v-if="attribute.type === 'text'"
						:id="'user-saml-' + key"
						:label="attribute.text"
						:modelValue="draft.sp?.[key] ?? ''"
						:required="attribute.required"
						@update:modelValue="(val) => setDraft('sp', key, val)" />
					<NcInputField
						v-else
						:id="'user-saml-' + key"
						:label="attribute.text"
						:modelValue="draft.sp?.[key] ?? ''"
						:required="attribute.required"
						@update:modelValue="(val) => setDraft('sp', key, val + '')" />
				</template>
			</NcSettingsSection>

			<!-- Identity Provider Data -->
			<NcSettingsSection :name="t('user_saml', 'Identity Provider Data')" :level="3">
				<NcInputField
					id="user-saml-entityId"
					v-model="draftIdp.entityId"
					:label="t('user_saml', 'Identifier of the IdP entity (must be a URI)')"
					required
					placeholder="https://example.com/auth/realms/default" />

				<NcInputField
					id="user-saml-singleSignOnService-url"
					v-model="draftIdp.ssoUrl"
					:label="t('user_saml', 'URL Target of the IdP where the SP will send the Authentication Request Message')"
					required
					placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcInputField
					id="user-saml-singleLogoutService-url"
					v-model="draftIdp.sloUrl"
					:label="t('user_saml', 'URL Location of the IdP where the SP will send the SLO Request')"
					placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcInputField
					id="user-saml-singleLogoutService-responseUrl"
					v-model="draftIdp.sloResponseUrl"
					:label="t('user_saml', 'URL Location of the IDP\'s SLO Response')"
					placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcTextArea
					id="user-saml-x509cert"
					v-model="draftIdp.x509cert"
					:label="t('user_saml', 'Public X.509 certificate of the IdP')" />

				<NcInputField
					id="user-saml-passthroughParameters"
					v-model="draftIdp.passthroughParameters"
					:label="t('user_saml', 'Request parameters to pass-through to IdP (comma separated list)')"
					placeholder="idp_hint,extra_parameter" />
			</NcSettingsSection>

			<!-- Attribute Mapping -->
			<NcSettingsSection
				v-if="showAttributeMapping"
				:name="t('user_saml', 'Attribute mapping')"
				:level="3"
				:description="t('user_saml', 'If you want to optionally map attributes to the user you can configure these here.')">
				<template v-for="(attribute, key) in attributeMappingSettings" :key="key">
					<NcInputField
						v-if="attribute.type === 'line'"
						:id="'user-saml-' + key"
						:label="attribute.text"
						:modelValue="draft['attribute-mapping']?.[key] ?? ''"
						:required="attribute.required"
						@update:modelValue="(val) => setDraft('attribute-mapping', key, val + '')" />
				</template>
			</NcSettingsSection>

			<!-- Security settings -->
			<NcSettingsSection
				:name="t('user_saml', 'Security settings')"
				:level="3"
				:description="t('user_saml', 'For increased security we recommend enabling the following settings if supported by your environment.')">
				<h4>{{ t('user_saml', 'Signatures and encryption offered') }}</h4>
				<NcCheckboxRadioSwitch
					v-for="(text, key) in securityOffer"
					:key="key"
					:modelValue="draft.security?.[key] === '1'"
					@update:modelValue="(val) => setDraft('security', key, val ? '1' : '0')">
					{{ text }}
				</NcCheckboxRadioSwitch>

				<h4>{{ t('user_saml', 'Signatures and encryption required') }}</h4>
				<NcCheckboxRadioSwitch
					v-for="(text, key) in securityRequired"
					:key="key"
					:modelValue="draft.security?.[key] === '1'"
					@update:modelValue="(val) => setDraft('security', key, val ? '1' : '0')">
					{{ text }}
				</NcCheckboxRadioSwitch>

				<h4>{{ t('user_saml', 'General') }}</h4>
				<template v-for="(attribute, key) in securityGeneral" :key="key">
					<NcInputField
						v-if="typeof attribute === 'object' && attribute.type === 'line'"
						:id="'user-saml-' + key"
						:label="attribute.text"
						:modelValue="draft.security?.[key] ?? ''"
						:required="attribute.required"
						placeholder="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"
						@update:modelValue="(val) => setDraft('security', key, val + '')" />
					<NcCheckboxRadioSwitch v-else
						:modelValue="draft.security?.[key] === '1'"
						@update:modelValue="(val) => setDraft('security', key, val ? '1' : '0')">
						{{ attribute }}
					</NcCheckboxRadioSwitch>
				</template>
			</NcSettingsSection>

			<!-- User Filtering -->
			<NcSettingsSection
				v-if="showAttributeMapping"
				:name="t('user_saml', 'User filtering')"
				:level="3"
				:description="t('user_saml', 'If you want to optionally restrict user login depending on user data, configure it here.')">
				<template v-for="(attribute, key) in userFilterSettings" :key="key">
					<NcInputField
						v-if="attribute.type === 'line'"
						:id="'user-saml-' + key"
						:label="attribute.text"
						:modelValue="draft['user-filter']?.[key] ?? ''"
						:required="attribute.required"
						:placeholder="attribute.placeholder"
						@update:modelValue="(val) => setDraft('user-filter', key, val + '')" />
				</template>
			</NcSettingsSection>

			<NcNoteCard v-if="metadataValid === true" type="success" class="dialog-status">
				{{ t('user_saml', 'Metadata valid') }}
			</NcNoteCard>

			<NcNoteCard v-else-if="metadataValid === false" type="error" class="dialog-status">
				{{ t('user_saml', 'Metadata invalid') }}
			</NcNoteCard>
		</div>
		<p v-else>{{ t('user_saml', 'Loading...')}}</p>
	</NcDialog>
</template>

<script setup lang="ts">
import type {
	DraftIdp,
	NameIdFormatsMap,
	Provider,
	ProviderConfig,
	SecurityGeneralMap,
	SecurityMap,
	SettingsMap,
} from '../types.ts'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import ProviderGeneralSection from './ProviderGeneralSection.vue'
import logger from '../logger.ts'

const open = defineModel<boolean>('open')

const props = defineProps<{
	provider: Provider
	generalSettings: SettingsMap
	spSettings: SettingsMap
	nameIdFormats: NameIdFormatsMap
	attributeMappingSettings: SettingsMap
	securityOffer: SecurityMap
	securityRequired: SecurityMap
	securityGeneral: SecurityGeneralMap
	userFilterSettings: SettingsMap
	showAttributeMapping: boolean
}>()

const emit = defineEmits<{
	providerNameChanged: [payload: { id: Provider['id'], name: string }]
	close: []
}>()

const providerConfig = ref<ProviderConfig>({})
const metadataValid = ref<boolean | null>(null) // null | true | false
const isSaving = ref<boolean>(false)
const isLoading = ref<boolean>(true)

/** Flat object for IDP fields (dotted API keys mapped to friendly names) */
const draftIdp = ref<DraftIdp>({
	entityId: '',
	ssoUrl: '',
	sloUrl: '',
	sloResponseUrl: '',
	x509cert: '',
	passthroughParameters: '',
})

/** Deep clone of providerConfig (all non-IDP categories), mutated by setDraft() */
const draft = ref<ProviderConfig>({})

// Map draftIdp property names to the API keys used in providerConfig.idp
const IDP_FIELD_MAP: Readonly<Record<keyof DraftIdp, string>> = {
	entityId: 'entityId',
	ssoUrl: 'singleSignOnService.url',
	sloUrl: 'singleLogoutService.url',
	sloResponseUrl: 'singleLogoutService.responseUrl',
	x509cert: 'x509cert',
	passthroughParameters: 'passthroughParameters',
}

const isDirty = computed<boolean>(() => {
	if (JSON.stringify(draft.value) !== JSON.stringify(providerConfig.value)) {
		return true
	}
	const idpCfg = providerConfig.value?.idp ?? {}
	return Object.entries(IDP_FIELD_MAP).some(([draftKey, apiKey]) => (draftIdp.value[draftKey as keyof DraftIdp] ?? '') !== (idpCfg[apiKey] ?? ''))
})

const metadataUrl = computed(() => generateUrl('/apps/user_saml/saml/metadata') + '?idp=' + props.provider.id)

interface NameIdFormatOption {
	value: string
	label: string
}

const nameIdFormatOptions = computed<NameIdFormatOption[]>(() => Object.entries(props.nameIdFormats)
	.map(([id, format]) => ({ value: id, label: format.label })))

/**
 * v-model for NcSelect — reads from draft, writes to draft immediately.
 * (Format changes are low-risk and have no dependent fields.)
 */
const nameIdFormatModel = computed<NameIdFormatOption | null>({
	get() {
		const currentId = draft.value?.sp?.['name-id-format']
			?? Object.keys(props.nameIdFormats).find((k) => props.nameIdFormats[k].selected)
		return nameIdFormatOptions.value.find((opt) => opt.value === currentId) ?? null
	},
	set(opt: NameIdFormatOption | null) {
		if (opt) {
			setDraft('sp', 'name-id-format', opt.value)
		}
	},
})

watch(() => [props.open, props.provider.id], async ([isOpen]) => {
	if (isOpen) {
		await loadProviderConfig()
	}
}, { immediate: true })

/**
 *
 */
async function loadProviderConfig(): Promise<void> {
	try {
		isLoading.value = true
		const { data } = await axios.get(generateUrl(`/apps/user_saml/settings/providerSettings/${props.provider.id}`))
		console.log(data)
		providerConfig.value = data
		syncDraftFromConfig()
		isLoading.value = false
		await testMetaData()
	} catch (error) {
		logger.error('Could not load provider settings', { error })
		showError(t('user_saml', 'Could not load provider settings'))
	}
}

/** Populate draft and draftIdp from the last loaded providerConfig. */
function syncDraftFromConfig(): void {
	draft.value = JSON.parse(JSON.stringify(providerConfig.value))
	const idpCfg = providerConfig.value?.idp ?? {}
	draftIdp.value = Object.fromEntries(Object.entries(IDP_FIELD_MAP).map(([draftKey, apiKey]) => [draftKey, idpCfg[apiKey] ?? ''])) as DraftIdp
	console.log(draft.value)
}

/**
 * Update one field in the local draft without touching the server.
 *
 * @param category The category that changed
 * @param key The key that chaned
 * @param value The new value
 */
function setDraft(category: keyof ProviderConfig, key: string, value: string): void {
	if (!draft.value[category]) {
		(draft.value as Record<string, Record<string, string>>)[category] = {}
	}
	(draft.value as Record<string, Record<string, string>>)[category][key] = value
}

/**
 * Send all changed fields to the server in parallel, then commit the draft as
 * the new baseline so isDirty resets to false.
 */
async function saveAll(): Promise<void> {
	isSaving.value = true
	try {
		const puts = []

		// All non-IDP categories
		const newConfigs: Record<string, string>  = {};
		for (const [category, settings] of Object.entries(draft.value)) {
			if (category === 'idp') {
				continue
			}
			const savedSettings = providerConfig.value[category] ?? {}
			for (const [key, value] of Object.entries(settings ?? {})) {
				if ((value ?? '') !== (savedSettings[key] ?? '')) {
					// 'attribute-mapping' and 'user-filter' need the 'saml-' prefix in the API
					const apiCategory = ['attribute-mapping', 'user-filter'].includes(category)
						? `saml-${category}`
						: category

					newConfigs[`${apiCategory}-${key}`] = String(value ?? '').trim();
				}
			}
		}

		// IDP fields
		const savedIdp = providerConfig.value?.idp ?? {}
		for (const [draftKey, apiKey] of Object.entries(IDP_FIELD_MAP)) {
			const draftVal = draftIdp.value[draftKey] ?? ''
			if (draftVal !== (savedIdp[apiKey] ?? '')) {
				newConfigs[`idp-${apiKey}`] = draftVal.trim();
			}
		}

		if (Object.keys(newConfigs).length > 0) {
			await axios.put(
				generateUrl(`/apps/user_saml/settings/providerSettings/${props.provider.id}`),
				{ newConfigs },
			)
		}

		// Commit draft → providerConfig
		providerConfig.value = JSON.parse(JSON.stringify(draft.value))
		if (!providerConfig.value.idp) {
			providerConfig.value.idp = {}
		}
		for (const [draftKey, apiKey] of Object.entries(IDP_FIELD_MAP)) {
			providerConfig.value.idp[apiKey] = draftIdp.value[draftKey] ?? ''
		}

		// Notify parent if the provider display name changed
		const newName = draft.value?.general?.idp0_display_name
		if (newName !== undefined) {
			emit('providerNameChanged', {
				id: props.provider.id,
				name: newName || t('user_saml', 'Provider {id}', { id: props.provider.id }),
			})
		}

		showSuccess(t('user_saml', 'Saved'))
		await testMetaData()
		emit('close')
	} catch (error) {
		logger.error('Could not save provider settings', { error })
		showError(t('user_saml', 'Could not save configuration'))
	} finally {
		isSaving.value = false
	}
}

/** Discard all unsaved edits by re-syncing draft from the last saved config. */
function cancelChanges(): void {
	syncDraftFromConfig()
	emit('close')
}

/**
 * When the dialog close button is clicked, discard unsaved changes before
 * propagating the close event so the parent unmounts cleanly.
 *
 * @param open Whether the dialog is open.
 */
function onOpenChanged(open: boolean): void {
	if (!open) {
		cancelChanges()
	}
}

/**
 *
 */
async function testMetaData(): Promise<void> {
	try {
		await axios.get(generateUrl(`/apps/user_saml/saml/metadata?idp=${props.provider.id}`))
		metadataValid.value = true
	} catch {
		metadataValid.value = false
	}
}
</script>

<style scoped>
.provider-settings {
	display: flex;
	flex-direction: column;
	padding-block-end: calc(var(--default-grid-baseline, 4px) * 2);
}

.dialog-status {
	margin: 0;
}
</style>
