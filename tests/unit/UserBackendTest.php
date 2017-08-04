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
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
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
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var UserBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->userManager = $this->createMock(IUserManager::class);
	}

	public function getMockedBuilder(array $mockedFunctions = []) {
		if($mockedFunctions !== []) {
			$this->userBackend = $this->getMockBuilder(UserBackend::class)
				->setConstructorArgs([
					$this->config,
					$this->urlGenerator,
					$this->session,
					$this->db,
					$this->userManager
				])
				->setMethods($mockedFunctions)
				->getMock();
		} else {
			$this->userBackend = new UserBackend(
				$this->config,
				$this->urlGenerator,
				$this->session,
				$this->db,
				$this->userManager
			);
		}
	}

	public function testGetBackendName() {
		$this->getMockedBuilder();
		$this->assertSame('user_saml', $this->userBackend->getBackendName());
	}

	public function testUpdateAttributesWithoutAttributes() {
		$this->getMockedBuilder(['getDisplayName']);
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('updateLastLoginTimestamp');
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn(null);
		$user
			->expects($this->never())
			->method('setEMailAddress');
		$this->userBackend
			->expects($this->once())
			->method('getDisplayName')
			->with('ExistingUser')
			->willReturn('');
		$this->userBackend->updateAttributes('ExistingUser', []);
	}

	public function testUpdateAttributesWithoutValidUser() {
		$this->getMockedBuilder();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn(null);
		$this->userBackend->updateAttributes('ExistingUser', []);
	}

	public function testUpdateAttributes() {
		$this->getMockedBuilder(['getDisplayName', 'setDisplayName']);
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'saml-attribute-mapping-email_mapping', '')
			->willReturn('email');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'saml-attribute-mapping-displayName_mapping', '')
			->willReturn('displayname');


		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('updateLastLoginTimestamp');
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn('old@example.com');
		$user
			->expects($this->once())
			->method('setEMailAddress')
			->with('new@example.com');
		$this->userBackend
			->expects($this->once())
			->method('getDisplayName')
			->with('ExistingUser')
			->willReturn('');
		$this->userBackend
			->expects($this->once())
			->method('setDisplayName')
			->with('ExistingUser', 'New Displayname');
		$this->userBackend->updateAttributes('ExistingUser', ['email' => 'new@example.com', 'displayname' => 'New Displayname']);
	}
}
