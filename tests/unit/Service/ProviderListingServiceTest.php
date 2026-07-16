<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Service;

use OC\Security\CSRF\CsrfTokenManager;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Service\ProviderListingService;
use OCP\IL10N;
use OCP\IURLGenerator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderListingServiceTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private SAMLSettings&MockObject $samlSettings;
	private CsrfTokenManager&MockObject $csrfTokenManager;
	private ProviderListingService $providerListingService;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->csrfTokenManager = $this->createMock(CsrfTokenManager::class);

		$this->l10n->expects($this->any())->method('t')->willReturnCallback(
			static fn (string $param): string => $param
		);

		$this->providerListingService = new ProviderListingService(
			$this->l10n,
			$this->urlGenerator,
			$this->samlSettings,
			$this->csrfTokenManager,
		);
	}

	#[DataProvider('dataTestGetSSODisplayName')]
	public function testGetSSODisplayName(string $configuredDisplayName, string $expected): void {
		$result = $this->invokePrivate($this->providerListingService, 'getSSODisplayName', [$configuredDisplayName]);

		$this->assertSame($expected, $result);
	}

	public static function dataTestGetSSODisplayName(): \Generator {
		yield ['My identity provider', 'My identity provider'];
		yield ['', 'SSO & SAML log in'];
	}
}
