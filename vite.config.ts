import type { UserConfig } from 'vitest/node'

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'path'

// replaced by vite
declare const __dirname: string

const isProduction = process.env.NODE_ENV === 'production'

export default createAppConfig({
	admin: join(__dirname, 'src', 'admin.ts'),
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
