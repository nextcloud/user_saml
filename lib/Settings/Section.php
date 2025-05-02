<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {

	public function __construct(
		private readonly IL10N $l,
		private readonly IURLGenerator $url,
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return 'saml';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->l->t('SSO & SAML authentication');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 75;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->url->imagePath('user_saml', 'app-dark.svg');
	}
}
