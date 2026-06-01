/*
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import './selectUserBackEnd.css'

document.addEventListener('DOMContentLoaded', () => {
	Array.from(document.getElementsByClassName('login-chose-saml-idp'))
		.forEach((element) => element.addEventListener('change', function(event) {
			const value = (<HTMLSelectElement>event.target).value ?? ''
			if (value !== '') {
				window.location.href = value
			}
		}))
})
