<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Support;

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Support\SystemReportSection;
use OCP\IL10N;
use OCP\SystemReport\SystemReportDetailFormat;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SystemReportSectionTest extends TestCase {
	private SAMLSettings&MockObject $samlSettings;
	private IL10N&MockObject $l10n;
	private SystemReportSection $section;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnCallback(fn (string $text, array $parameters = []) => vsprintf($text, $parameters));

		$this->section = new SystemReportSection(
			$this->samlSettings,
			$this->l10n,
		);
	}

	public function testGetIdAndTitle(): void {
		$this->assertSame('saml', $this->section->getId());
		$this->assertSame('SAML', $this->section->getTitle());
	}

	public function testGetDetailsReturnsNothingWithoutConfiguredIdps(): void {
		$this->samlSettings->method('getListOfIdps')
			->willReturn([]);

		$this->assertSame([], $this->section->getDetails());
	}

	public function testGetDetailsNeverIncludesThePrivateKey(): void {
		$this->samlSettings->method('getListOfIdps')
			->willReturn([1 => 'My IdP']);
		$this->samlSettings->method('get')
			->with(1)
			->willReturn([
				'idp-entityId' => 'https://idp.example.com',
				'sp-privateKey' => 'super-secret-private-key',
			]);

		$details = $this->section->getDetails();
		$this->assertCount(1, $details);
		$this->assertStringNotContainsString('super-secret-private-key', $details[0]->getContent());
		$this->assertStringContainsString('idp-entityId: https://idp.example.com', $details[0]->getContent());
	}

	public function testGetDetailsOnlyReportsCertificatePresence(): void {
		$this->samlSettings->method('getListOfIdps')
			->willReturn([1 => 'My IdP']);
		$this->samlSettings->method('get')
			->with(1)
			->willReturn([
				'idp-x509cert' => '-----BEGIN CERTIFICATE-----MIIB...-----END CERTIFICATE-----',
				'sp-x509cert' => '',
			]);

		$content = $this->section->getDetails()[0]->getContent();
		$this->assertStringNotContainsString('BEGIN CERTIFICATE', $content);
		$this->assertStringContainsString('idp-x509cert: configured', $content);
		$this->assertStringContainsString('sp-x509cert: not configured', $content);
	}

	public function testGetDetailsUsesFallbackTitleWhenDisplayNameIsEmpty(): void {
		$this->samlSettings->method('getListOfIdps')
			->willReturn([3 => '']);
		$this->samlSettings->method('get')
			->with(3)
			->willReturn([]);

		$this->assertSame('SAML provider #3', $this->section->getDetails()[0]->getTitle());
	}

	public function testGetDetailsUsesPreformattedFormat(): void {
		$this->samlSettings->method('getListOfIdps')
			->willReturn([1 => 'My IdP']);
		$this->samlSettings->method('get')
			->with(1)
			->willReturn([]);

		$this->assertSame(SystemReportDetailFormat::Preformatted, $this->section->getDetails()[0]->getFormat());
	}
}
