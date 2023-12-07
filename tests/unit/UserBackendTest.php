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

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\UserChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserBackendTest extends TestCase {
	/** @var UserData|MockObject */
	private $userData;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var ISession|MockObject */
	private $session;
	/** @var IDBConnection|MockObject */
	private $db;
	/** @var IUserManager|MockObject */
	private $userManager;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var UserBackend|MockObject */
	private $userBackend;
	/** @var SAMLSettings|MockObject */
	private $SAMLSettings;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;
	/** @var IAvatarManager|MockObject */
	private $avatarManager;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->SAMLSettings = $this->getMockBuilder(SAMLSettings::class)->disableOriginalConstructor()->getMock();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userData = $this->createMock(UserData::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->avatarManager = $this->createMock(IAvatarManager::class);
	}

	public function getMockedBuilder(array $mockedFunctions = []) {
		if ($mockedFunctions !== []) {
			$this->userBackend = $this->getMockBuilder(UserBackend::class)
				->setConstructorArgs([
					$this->config,
					$this->urlGenerator,
					$this->session,
					$this->db,
					$this->userManager,
					$this->groupManager,
					$this->SAMLSettings,
					$this->logger,
					$this->userData,
					$this->eventDispatcher,
					$this->avatarManager
				])
				->setMethods($mockedFunctions)
				->getMock();
		} else {
			$this->userBackend = new UserBackend(
				$this->config,
				$this->urlGenerator,
				$this->session,
				$this->db,
				$this->userManager,
				$this->groupManager,
				$this->SAMLSettings,
				$this->logger,
				$this->userData,
				$this->eventDispatcher,
				$this->avatarManager
			);
		}
	}

	public function testGetBackendName() {
		$this->getMockedBuilder();
		$this->assertSame('user_saml', $this->userBackend->getBackendName());
	}

	public function testUpdateAttributesWithoutAttributes() {
		$this->getMockedBuilder(['getDisplayName']);
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->config->method('getAppValue')
			->willReturnCallback(function ($appId, $key, $default) {
				return $default;
			});

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		if (method_exists($user, 'getSystemEMailAddress')) {
			$user
				->expects($this->once())
				->method('getSystemEMailAddress')
				->willReturn(null);
		} else {
			$user
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn(null);
		}
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

		$this->config->method('getAppValue')
			->willReturnCallback(function ($appId, $key, $default) {
				return $default;
			});

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn(null);
		$this->userBackend->updateAttributes('ExistingUser', []);
	}

	public function testUpdateAttributes() {
		$this->getMockedBuilder(['getDisplayName', 'setDisplayName']);
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$groupC = $this->createMock(IGroup::class);

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
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('user_saml', 'saml-attribute-mapping-quota_mapping', '')
			->willReturn('quota');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('user_saml', 'saml-attribute-mapping-group_mapping', '')
			->willReturn('groups');

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		if (method_exists($user, 'getSystemEMailAddress')) {
			$user
				->expects($this->once())
				->method('getSystemEMailAddress')
				->willReturn('old@example.com');
		} else {
			$user
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn('old@example.com');
		}
		$user
			->expects($this->once())
			->method('setEMailAddress')
			->with('new@example.com');
		$user
			->expects($this->once())
			->method('setQuota')
			->with('50MB');
		$this->userBackend
			->expects($this->once())
			->method('getDisplayName')
			->with('ExistingUser')
			->willReturn('');
		$this->userBackend
			->expects($this->once())
			->method('setDisplayName')
			->with('ExistingUser', 'New Displayname');

		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['groupA', 'groupB']);
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('groupC')
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('groupC');

		// updateAttributes first adds new groups, then removes old ones
		// In this test groupA is removed from the user, groupB is unchanged
		// and groupC is added
		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(['groupC'], ['groupA'])
			->willReturnOnConsecutiveCalls($groupC, $groupA);
		$groupA
			->expects($this->once())
			->method('removeUser')
			->with($user);
		$groupC
			->expects($this->once())
			->method('addUser')
			->with($user);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new UserChangedEvent($user, 'displayName', 'New Displayname', ''));

		$this->userBackend->updateAttributes('ExistingUser', [
			'email' => 'new@example.com',
			'displayname' => 'New Displayname',
			'quota' => '50MB',
			'groups' => ['groupB', 'groupC'],
		]);
	}

	public function testUpdateAttributesQuotaDefaultFallback() {
		$this->getMockedBuilder(['getDisplayName', 'setDisplayName']);
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);


		$this->config->method('getAppValue')
			->willReturnCallback(function ($appId, $key, $default) {
				switch ($key) {
					case 'saml-attribute-mapping-email_mapping':
						return 'email';
					case 'saml-attribute-mapping-displayName_mapping':
						return 'displayname';
					case 'saml-attribute-mapping-quota_mapping':
						return 'quota';
				}
				return $default;
			});

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		if (method_exists($user, 'getSystemEMailAddress')) {
			$user
				->expects($this->once())
				->method('getSystemEMailAddress')
				->willReturn('old@example.com');
		} else {
			$user
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn('old@example.com');
		}
		$user
			->expects($this->once())
			->method('setEMailAddress')
			->with('new@example.com');
		$user
			->expects($this->once())
			->method('setQuota')
			->with('default');
		$this->userBackend
			->expects($this->once())
			->method('getDisplayName')
			->with('ExistingUser')
			->willReturn('');
		$this->userBackend
			->expects($this->once())
			->method('setDisplayName')
			->with('ExistingUser', 'New Displayname');
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new UserChangedEvent($user, 'displayName', 'New Displayname', ''));
		$this->userBackend->updateAttributes('ExistingUser', ['email' => 'new@example.com', 'displayname' => 'New Displayname', 'quota' => '']);
	}
}
