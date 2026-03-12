<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :open="open"
			  :name="t('user_saml', 'Configure: {name}', { name: provider.name })"
			  :close-on-click-outside="false"
			  isForm
			  size="large"
			  @update:open="onDialogClose"
			  @submit="saveAll">

		<template #actions>
			<NcButton variant="secondary"
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

		<div class="provider-settings">

			<!-- General (per-provider) -->
			<NcSettingsSection :name="t('user_saml', 'General')" :level="3">
				<template v-for="(attribute, key) in generalSettings" :key="key">
					<p v-if="attribute.type === 'checkbox' && !attribute.global">
						<NcCheckboxRadioSwitch
							:checked="draft.general?.[key] === '1'"
							@update:checked="(val) => setDraft('general', key, val ? '1' : '0')">
							{{ attribute.text }}
						</NcCheckboxRadioSwitch>
						<NcNoteCard v-if="key === 'is_saml_request_using_post'" type="warning">
							{{ t('user_saml', 'This feature might not work with all identity providers. Use only if your IdP specifically requires POST binding for SAML requests.') }}
						</NcNoteCard>
					</p>
					<NcInputField v-else-if="attribute.type === 'line' && attribute.global === undefined"
								  :id="'user-saml-general-' + key"
								  :label="attribute.text"
								  :model-value="draft.general?.[key] ?? ''"
								  :required="attribute.required"
								  @update:model-value="(val) => setDraft('general', key, val)" />
				</template>
			</NcSettingsSection>

			<!-- Service Provider Data -->
			<NcSettingsSection :name="t('user_saml', 'Service Provider Data')" :level="3"
							   :description="t('user_saml', 'If your Service Provider should use certificates you can optionally specify them here.')">
				<NcSelect :label="t('user_saml', 'Name ID format')"
						  :options="nameIdFormatOptions"
						  v-model="nameIdFormatModel"
						  :clearable="false" />

				<template v-for="(attribute, key) in spSettings" :key="key">
					<NcTextArea v-if="attribute.type === 'text'"
								:id="'user-saml-' + key"
								:label="attribute.text"
								:model-value="draft.sp?.[key] ?? ''"
								:required="attribute.required"
								@update:model-value="(val) => setDraft('sp', key, val)" />
					<NcInputField v-else
								  :id="'user-saml-' + key"
								  :label="attribute.text"
								  :model-value="draft.sp?.[key] ?? ''"
								  :required="attribute.required"
								  @update:model-value="(val) => setDraft('sp', key, val)" />
				</template>
			</NcSettingsSection>

			<!-- Identity Provider Data -->
			<NcSettingsSection :name="t('user_saml', 'Identity Provider Data')" :level="3">
				<NcInputField id="user-saml-entityId"
							  :label="t('user_saml', 'Identifier of the IdP entity (must be a URI)')"
							  v-model="draftIdp.entityId"
							  required
							  placeholder="https://example.com/auth/realms/default" />

				<NcInputField id="user-saml-singleSignOnService-url"
							  :label="t('user_saml', 'URL Target of the IdP where the SP will send the Authentication Request Message')"
							  v-model="draftIdp.ssoUrl"
							  required
							  placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcInputField id="user-saml-singleLogoutService-url"
							  :label="t('user_saml', 'URL Location of the IdP where the SP will send the SLO Request')"
							  v-model="draftIdp.sloUrl"
							  placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcInputField id="user-saml-singleLogoutService-responseUrl"
							  :label="t('user_saml', 'URL Location of the IDP\'s SLO Response')"
							  v-model="draftIdp.sloResponseUrl"
							  placeholder="https://example.com/auth/realms/default/protocol/saml" />

				<NcTextArea id="user-saml-x509cert"
							:label="t('user_saml', 'Public X.509 certificate of the IdP')"
							v-model="draftIdp.x509cert" />

				<NcInputField id="user-saml-passthroughParameters"
							  :label="t('user_saml', 'Request parameters to pass-through to IdP (comma separated list)')"
							  v-model="draftIdp.passthroughParameters"
							  placeholder="idp_hint,extra_parameter" />
			</NcSettingsSection>

			<!-- Attribute Mapping -->
			<NcSettingsSection v-if="showAttributeMapping"
							   :name="t('user_saml', 'Attribute mapping')"
							   :level="3"
							   :description="t('user_saml', 'If you want to optionally map attributes to the user you can configure these here.')">
				<template v-for="(attribute, key) in attributeMappingSettings" :key="key">
					<NcInputField v-if="attribute.type === 'line'"
								  :id="'user-saml-' + key"
								  :label="attribute.text"
								  :model-value="draft['attribute-mapping']?.[key] ?? ''"
								  :required="attribute.required"
								  @update:model-value="(val) => setDraft('attribute-mapping', key, val)" />
				</template>
			</NcSettingsSection>

			<!-- Security settings -->
			<NcSettingsSection :name="t('user_saml', 'Security settings')"
							   :level="3"
							   :description="t('user_saml', 'For increased security we recommend enabling the following settings if supported by your environment.')">
				<h4>{{ t('user_saml', 'Signatures and encryption offered') }}</h4>
				<p v-for="(text, key) in securityOffer" :key="key">
					<NcCheckboxRadioSwitch
						:model-value="draft.security?.[key] === '1'"
						@update:model-value="(val) => setDraft('security', key, val ? '1' : '0')">
						{{ text }}
					</NcCheckboxRadioSwitch>
				</p>

				<h4>{{ t('user_saml', 'Signatures and encryption required') }}</h4>
				<p v-for="(text, key) in securityRequired" :key="key">
					<NcCheckboxRadioSwitch
						:model-value="draft.security?.[key] === '1'"
						@update:model-value="(val) => setDraft('security', key, val ? '1' : '0')">
						{{ text }}
					</NcCheckboxRadioSwitch>
				</p>

				<h4>{{ t('user_saml', 'General') }}</h4>
				<template v-for="(attribute, key) in securityGeneral" :key="key">
					<NcInputField v-if="typeof attribute === 'object' && attribute.type === 'line'"
								  :id="'user-saml-' + key"
								  :label="attribute.text"
								  :model-value="draft.security?.[key] ?? ''"
								  :required="attribute.required"
								  placeholder="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"
								  @update:model-value="(val) => setDraft('security', key, val)" />
					<p v-else>
						<NcCheckboxRadioSwitch
							:model-value="draft.security?.[key] === '1'"
							@update:model-value="(val) => setDraft('security', key, val ? '1' : '0')">
							{{ attribute }}
						</NcCheckboxRadioSwitch>
					</p>
				</template>
			</NcSettingsSection>

			<!-- User Filtering -->
			<NcSettingsSection v-if="showAttributeMapping"
							   :name="t('user_saml', 'User filtering')"
							   :level="3"
							   :description="t('user_saml', 'If you want to optionally restrict user login depending on user data, configure it here.')">
				<template v-for="(attribute, key) in userFilterSettings" :key="key">
					<NcInputField v-if="attribute.type === 'line'"
								  :id="'user-saml-' + key"
								  :label="attribute.text"
								  :model-value="draft['user-filter']?.[key] ?? ''"
								  :required="attribute.required"
								  :placeholder="attribute.placeholder"
								  @update:model-value="(val) => setDraft('user-filter', key, val)" />
				</template>
			</NcSettingsSection>
		</div>

		<NcNoteCard v-if="metadataValid === true" type="success" class="dialog-status">
			{{ t('user_saml', 'Metadata valid') }}
		</NcNoteCard>
		<NcNoteCard v-else-if="metadataValid === false" type="error" class="dialog-status">
			{{ t('user_saml', 'Metadata invalid') }}
		</NcNoteCard>
	</NcDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import logger from '../logger.js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'

const props = defineProps({
	open: {
		type: Boolean,
		required: true,
	},
	provider: {
		type: Object,
		required: true,
	},
	generalSettings: {
		type: Object,
		default: () => ({}),
	},
	spSettings: {
		type: Object,
		default: () => ({}),
	},
	nameIdFormats: {
		type: Object,
		default: () => ({}),
	},
	attributeMappingSettings: {
		type: Object,
		default: () => ({}),
	},
	securityOffer: {
		type: Object,
		default: () => ({}),
	},
	securityRequired: {
		type: Object,
		default: () => ({}),
	},
	securityGeneral: {
		type: Object,
		default: () => ({}),
	},
	userFilterSettings: {
		type: Object,
		default: () => ({}),
	},
	showAttributeMapping: {
		type: Boolean,
		default: true,
	},
})

const emit = defineEmits(['update:open', 'provider-name-changed', 'close'])

const providerConfig = ref({})
const metadataValid = ref(null) // null | true | false
const isSaving = ref(false)

/** Flat object for IDP fields (dotted API keys mapped to friendly names) */
const draftIdp = ref({
	entityId: '',
	ssoUrl: '',
	sloUrl: '',
	sloResponseUrl: '',
	x509cert: '',
	passthroughParameters: '',
})

/** Deep clone of providerConfig (all non-IDP categories), mutated by setDraft() */
const draft = ref({})

// Map draftIdp property names to the API keys used in providerConfig.idp
const IDP_FIELD_MAP = {
	entityId: 'entityId',
	ssoUrl: 'singleSignOnService.url',
	sloUrl: 'singleLogoutService.url',
	sloResponseUrl: 'singleLogoutService.responseUrl',
	x509cert: 'x509cert',
	passthroughParameters: 'passthroughParameters',
}

const isDirty = computed(() => {
	if (JSON.stringify(draft.value) !== JSON.stringify(providerConfig.value)) return true
	const idpCfg = providerConfig.value?.idp ?? {}
	return Object.entries(IDP_FIELD_MAP).some(
		([draftKey, apiKey]) => (draftIdp.value[draftKey] ?? '') !== (idpCfg[apiKey] ?? '')
	)
})

const metadataUrl = computed(() =>
	generateUrl('/apps/user_saml/saml/metadata') + '?idp=' + props.provider.id
)

const nameIdFormatOptions = computed(() =>
	Object.entries(props.nameIdFormats).map(([id, format]) => ({ id, label: format.label }))
)

/**
 * v-model for NcSelect — reads from draft, writes to draft immediately.
 * (Format changes are low-risk and have no dependent fields.)
 */
const nameIdFormatModel = computed({
	get() {
		const currentId = draft.value?.sp?.['name-id-format']
			?? Object.keys(props.nameIdFormats).find(k => props.nameIdFormats[k].selected)
		return nameIdFormatOptions.value.find(opt => opt.id === currentId) ?? null
	},
	set(opt) {
		if (opt) setDraft('sp', 'name-id-format', opt.id)
	},
})

watch(() => [props.open, props.provider.id], async ([isOpen]) => {
	if (isOpen) {
		await loadProviderConfig()
	}
}, { immediate: true })

async function loadProviderConfig() {
	try {
		const { data } = await axios.get(
			generateUrl(`/apps/user_saml/settings/providerSettings/${props.provider.id}`)
		)
		providerConfig.value = data
		syncDraftFromConfig()
		await testMetaData()
	} catch (error) {
		logger.error('Could not load provider settings', { error })
		showError(t('user_saml', 'Could not load provider settings'))
	}
}

/** Populate draft and draftIdp from the last loaded providerConfig. */
function syncDraftFromConfig() {
	draft.value = JSON.parse(JSON.stringify(providerConfig.value))
	const idpCfg = providerConfig.value?.idp ?? {}
	draftIdp.value = Object.fromEntries(
		Object.entries(IDP_FIELD_MAP).map(([draftKey, apiKey]) => [draftKey, idpCfg[apiKey] ?? ''])
	)
}

/** Update one field in the local draft without touching the server. */
function setDraft(category, key, value) {
	if (!draft.value[category]) {
		draft.value[category] = {}
	}
	draft.value[category][key] = value
}

/**
 * Send all changed fields to the server in parallel, then commit the draft as
 * the new baseline so isDirty resets to false.
 */
async function saveAll() {
	console.log('saveAll')
	isSaving.value = true
	try {
		const puts = []

		// All non-IDP categories
		for (const [category, settings] of Object.entries(draft.value)) {
			if (category === 'idp') continue
			const savedSettings = providerConfig.value[category] ?? {}
			for (const [key, value] of Object.entries(settings ?? {})) {
				if ((value ?? '') !== (savedSettings[key] ?? '')) {
					// 'attribute-mapping' and 'user-filter' need the 'saml-' prefix in the API
					const apiCategory = ['attribute-mapping', 'user-filter'].includes(category)
						? `saml-${category}`
						: category
					puts.push(
						axios.put(
							generateUrl(`/apps/user_saml/settings/providerSettings/${props.provider.id}`),
							{ configKey: `${apiCategory}-${key}`, configValue: String(value ?? '').trim() },
						)
					)
				}
			}
		}

		// IDP fields
		const savedIdp = providerConfig.value?.idp ?? {}
		for (const [draftKey, apiKey] of Object.entries(IDP_FIELD_MAP)) {
			const draftVal = draftIdp.value[draftKey] ?? ''
			if (draftVal !== (savedIdp[apiKey] ?? '')) {
				puts.push(
					axios.put(
						generateUrl(`/apps/user_saml/settings/providerSettings/${props.provider.id}`),
						{ configKey: `idp-${apiKey}`, configValue: draftVal.trim() },
					)
				)
			}
		}

		await Promise.all(puts)

		// Commit draft → providerConfig
		providerConfig.value = JSON.parse(JSON.stringify(draft.value))
		if (!providerConfig.value.idp) providerConfig.value.idp = {}
		for (const [draftKey, apiKey] of Object.entries(IDP_FIELD_MAP)) {
			providerConfig.value.idp[apiKey] = draftIdp.value[draftKey] ?? ''
		}

		// Notify parent if the provider display name changed
		const newName = draft.value?.general?.['idp0_display_name']
		if (newName !== undefined) {
			emit('provider-name-changed', {
				id: props.provider.id,
				name: newName || t('user_saml', 'Provider {id}', { id: props.provider.id }),
			})
		}

		showSuccess(t('user_saml', 'Saved'))
		await testMetaData()
	} catch (error) {
		logger.error('Could not save provider settings', { error })
		showError(t('user_saml', 'Could not save configuration'))
	} finally {
		isSaving.value = false
	}
	emit('close');
}

/** Discard all unsaved edits by re-syncing draft from the last saved config. */
function cancelChanges() {
	syncDraftFromConfig()
	emit('close')
}

/**
 * When the dialog close button is clicked, discard unsaved changes before
 * propagating the close event so the parent unmounts cleanly.
 */
function onDialogClose(val) {
	if (!val) cancelChanges()
	emit('update:open', val)
}

async function testMetaData() {
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
	gap: calc(var(--default-grid-baseline, 4px) * 4);
	padding-block-end: calc(var(--default-grid-baseline, 4px) * 2);
}

.dialog-status {
	margin: 0;
}
</style>
