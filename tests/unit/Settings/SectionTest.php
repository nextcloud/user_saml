<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Tests\Settings;

class SectionTest extends \Test\TestCase  {
	/** @var \OCA\User_SAML\Settings\Section */
	private $section;
	/** @var \OCP\IL10N */
	private $l10n;

	public function setUp() {
		$this->l10n = $this->createMock(\OCP\IL10N::class);
		$this->section = new \OCA\User_SAML\Settings\Section(
			$this->l10n
		);

		return parent::setUp();
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
}
