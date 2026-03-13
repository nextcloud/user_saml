<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<template v-for="(attribute, key) in generalSettings" :key="key">
		<p v-if="attribute.type === 'checkbox' && !attribute.global && attribute.provider_type === '' || attribute.provider_type === type">
			<NcCheckboxRadioSwitch
				:modelValue="modelValue[key] === '1'"
				@update:checked="(val: boolean) => onChange(key, val ? '1' : '0')">
				{{ attribute.text }}
			</NcCheckboxRadioSwitch>
			<NcNoteCard v-if="key === 'is_saml_request_using_post'" type="warning">
				{{ t('user_saml', 'This feature might not work with all identity providers. Use only if your IdP specifically requires POST binding for SAML requests.') }}
			</NcNoteCard>
		</p>
		<NcInputField
			v-else-if="attribute.type === 'line' && attribute.global === undefined"
			:id="'user-saml-general-' + key"
			:label="attribute.text"
			:modelValue="modelValue[key] ?? ''"
			:required="attribute.required"
			@update:modelValue="(val: string) => onChange(key, val)" />
	</template>
</template>

<script setup lang="ts">
import type { SettingsMap } from '../types.ts'

import { translate as t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const props = defineProps<{
	generalSettings: SettingsMap
	modelValue: Record<string, string>
}>()

const emit = defineEmits<{
	'update:modelValue': [value: Record<string, string>]
	fieldChange: [key: string, value: string]
}>()

/**
 *
 * @param key The key that changed
 * @param value The new value
 */
function onChange(key: string, value: string): void {
	emit('update:modelValue', { ...props.modelValue, [key]: value })
	emit('fieldChange', key, value)
}
</script>
