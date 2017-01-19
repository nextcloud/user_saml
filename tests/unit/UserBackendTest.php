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

use OCA\User_SAML\UserBackend;
use OCP\IConfig;
use OCP\IDb;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserBackend;
use Test\TestCase;

class UserBackendTest extends TestCase   {
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $db;
	/** @var IUserBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->db = $this->createMock(IDb::class);

		$this->userBackend = new UserBackend(
			$this->config,
			$this->urlGenerator,
			$this->session,
			$this->db
		);
	}

	public function testGetBackendName() {
		$this->assertSame('user_saml', $this->userBackend->getBackendName());
	}
}
