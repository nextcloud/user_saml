// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { UserConfig } from 'vitest/node'

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'path'

const isProduction = process.env.NODE_ENV === 'production'

export default createAppConfig({
	admin: join(import.meta.dirname, 'src', 'admin.ts'),
	selectUserBackEnd: join(import.meta.dirname, 'src', 'selectUserBackEnd.ts'),
}, {
	minify: isProduction,
	thirdPartyLicense: false,
	extractLicenseInformation: true,
	createEmptyCSSEntryPoints: true,
	emptyOutputDirectory: {
		// also clear the css directory
		additionalDirectories: ['css'],
	},
})
