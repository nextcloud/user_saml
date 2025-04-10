<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;

class SectionTest extends \Test\TestCase {
	/** @var \OCA\User_SAML\Settings\Section */
	private $section;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		$this->l10n = $this->createMock(\OCP\IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->section = new \OCA\User_SAML\Settings\Section(
			$this->l10n,
			$this->urlGenerator
		);

		parent::setUp();
	}

	public function testGetId() {
		$this->assertSame('saml', $this->section->getID());
	}

	public function testGetName() {
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('SSO & SAML authentication')
			->willReturn('SAML authentication');

		$this->assertSame('SAML authentication', $this->section->getName());
	}

	public function testGetPriority() {
		$this->assertSame(75, $this->section->getPriority());
	}

	public function testGetIcon() {
		$this->urlGenerator
			->expects($this->once())
			->method('imagePath')
			->with('user_saml', 'app-dark.svg')
			->willReturn('/apps/user_saml/myicon.svg');
		$this->assertSame('/apps/user_saml/myicon.svg', $this->section->getIcon());
	}
}
