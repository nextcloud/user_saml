/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface Provider {
	id: number | string
	name: string
}

export interface SettingAttribute {
	type: 'checkbox' | 'line' | 'text'
	text: string
	required?: boolean
	global?: boolean
	placeholder?: string
	provider_type: string
}

export interface NameIdFormat {
	label: string
	selected?: boolean
}

export interface ProviderConfig {
	general?: Record<string, string>
	sp?: Record<string, string>
	idp?: Record<string, string>
	security?: Record<string, string>
	'attribute-mapping'?: Record<string, string>
	'user-filter'?: Record<string, string>
}

export interface DraftIdp {
	entityId: string
	ssoUrl: string
	sloUrl: string
	sloResponseUrl: string
	x509cert: string
	passthroughParameters: string
}

export type GlobalConfig = Record<string, string | boolean>
export type SettingsMap = Record<string, SettingAttribute>
export type SecurityMap = Record<string, string>
export type SecurityGeneralMap = Record<string, string | SettingAttribute>
export type NameIdFormatsMap = Record<string, NameIdFormat>
