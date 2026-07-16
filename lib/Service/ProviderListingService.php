<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Service;

use OC\Security\CSRF\CsrfTokenManager;
use OCA\User_SAML\SAMLSettings;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProviderListingService {
	public function __construct(
		private readonly IL10N $l10n,
		private readonly IUrlGenerator $urlGenerator,
		private readonly SAMLSettings $samlSettings,
		private readonly CsrfTokenManager $csrfTokenManager,
	) {
	}

	/**
	 * Return the display name of the SSO identity provider.
	 */
	protected function getSSODisplayName(?string $displayName): string {
		if ($displayName === null || $displayName === '') {
			$displayName = $this->l10n->t('SSO & SAML log in');
		}

		return $displayName;
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws \OCP\DB\Exception
	 */
	private function getSSOUrl(string $redirectUrl, int $idp): string {
		$originalUrl = '';
		if (!empty($redirectUrl)) {
			$originalUrl = $this->urlGenerator->getAbsoluteURL($redirectUrl);
		}

		$csrfToken = $this->csrfTokenManager->getToken();

		$settings = $this->samlSettings->get($idp);
		$method = $settings['general-is_saml_request_using_post'] ?? 'get';

		return $this->urlGenerator->linkToRouteAbsolute(
			'user_saml.SAML.login',
			[
				'requesttoken' => $csrfToken->getEncryptedValue(),
				'originalUrl' => $originalUrl,
				'idp' => (string)$idp,
				'method' => $method,
			]
		);
	}

	/**
	 * Get the IdPs showed at the login page
	 *
	 * @return list<array{url: string, display-name: string}>
	 */
	public function getIdps(string $redirectUrl): array {
		$result = [];
		$idps = $this->samlSettings->getListOfIdps();
		foreach ($idps as $idpId => $displayName) {
			$result[] = [
				'url' => $this->getSSOUrl($redirectUrl, $idpId),
				'display-name' => $this->getSSODisplayName($displayName),
			];
		}

		return $result;
	}
}
