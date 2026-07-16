<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\AlternativeLogin;

use OCA\User_SAML\Service\ProviderListingService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Authentication\IAlternativeLoginProvider;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * @psalm-suppress UndefinedClass IAlternativeLoginProvider is only defined in NC >= 34
 */
class AlternativeLoginProvider implements IAlternativeLoginProvider {
	public function __construct(
		private readonly IRequest $request,
		private readonly IUrlGenerator $urlGenerator,
		private readonly ProviderListingService $providerListingService,
		private readonly IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getAlternativeLogins(): array {
		$type = $this->appConfig->getAppValueString('type');

		if ($type !== 'saml') {
			return [];
		}

		$redirectUrl = $this->request->getParam('redirect_url') ?? '';
		$absoluteRedirectUrl = $this->urlGenerator->getAbsoluteURL($redirectUrl);
		return array_map(fn (array $idp): AlternativeLogin
			=> new AlternativeLogin($idp['display-name'], $idp['url']), $this->providerListingService->getIdps($absoluteRedirectUrl));
	}
}
