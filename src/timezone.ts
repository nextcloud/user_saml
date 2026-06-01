/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import logger from './logger.ts'

logger.debug('updating timezone and offset for SAML user')

try {
	await axios.post(generateUrl('/apps/user_saml/config/timezone'), {
		timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
		timezoneOffset: String(-new Date().getTimezoneOffset() / 60),
	})
	logger.info('timezone and offset updated for SAML user')
} catch (error) {
	logger.error('could not set timezone and offset for SAML user', { error })
}
