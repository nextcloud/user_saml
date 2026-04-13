<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\SAMLSettings;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class GroupManagerTest extends TestCase {

	private IDBConnection&MockObject $db;
	private IGroupManager&MockObject $groupManager;
	private GroupBackend&MockObject $ownGroupBackend;
	private IAppConfig&MockObject $appConfig;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IJobList&MockObject $jobList;
	private SAMLSettings&MockObject $settings;
	private LoggerInterface&MockObject $logger;

	#[Override]
	protected function setUp(): void {
		parent::setUp();
		$this->db = $this->createMock(IDBConnection::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->ownGroupBackend = $this->createMock(GroupBackend::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->settings = $this->createMock(SAMLSettings::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * @param list<non-empty-string> $mockedFunctions
	 */
	public function getPartialGroupManager(array $mockedFunctions = []): MockObject&GroupManager {
		return $this->getMockBuilder(GroupManager::class)
			->setConstructorArgs([
				$this->db,
				$this->groupManager,
				$this->ownGroupBackend,
				$this->appConfig,
				$this->eventDispatcher,
				$this->jobList,
				$this->settings,
				$this->logger,
			])
			->onlyMethods($mockedFunctions)
			->getMock();
	}
	public function getRealGroupManager(): GroupManager {
		return new GroupManager(
			$this->db,
			$this->groupManager,
			$this->ownGroupBackend,
			$this->appConfig,
			$this->eventDispatcher,
			$this->jobList,
			$this->settings,
			$this->logger,
		);
	}

	public function testUpdateUserGroups(): void {
		// Case: The known memberships of the user are groupA and groupB. The new
		// memberships are GroupB and GroupC. Hence, the user must be unassigned
		// from GroupA and assigned to GroupC, while the GroupB association remains
		// unchanged.

		$this->appConfig->expects($this->any())
			->method('getAppValueString')
			->with(GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION)
			->willReturn(\json_encode(['groups' => ['groupA'], 'dropAfter' => time() + 2000]));

		$groupManager = $this->getPartialGroupManager(['handleUserUnassignedFromGroups', 'handleUserAssignedToGroups', 'translateGroupToIds', 'hasSamlBackend']);
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$groupA
			->method('getBackendNames')
			->willReturn(['Database']);
		$groupA->method('getGID')
			->willReturn('groupA');
		$groupB = $this->createMock(IGroup::class);
		$groupB
			->method('getBackendNames')
			->willReturn(['Database']);
		$groupB->method('getGID')
			->willReturn('groupB');
		$groupManager
			->expects($this->once())
			->method('translateGroupToIds')
			->with(['groupB', 'groupC']);
		// assert user is actually assigned to groupA and groupB
		$this->groupManager
			->expects($this->once())
			->method('getUserGroups')
			->with($user)
			->willReturn([$groupA, $groupB]);
		$this->groupManager
			->method('get')
			->willReturnCallback(fn (string $groupId): ?IGroup => match ($groupId) {
				'groupA' => $groupA,
				'groupB' => $groupB,
				default => null,
			});
		// assert all groups are supplied by SAML backend
		$groupManager
			->method('hasSamlBackend')
			->willReturn(true);
		// assert removing membership to groupA
		$groupManager
			->expects($this->once())
			->method('handleUserUnassignedFromGroups')
			->with($user, ['groupA']);
		// assert adding membership to groupC
		$groupManager
			->expects($this->once())
			->method('handleUserAssignedToGroups')
			->with($user, ['groupC']);

		// assert SAML provides user groups groupB and groupC
		$this->invokePrivate($groupManager, 'updateUserGroups', [$user, ['groupB', 'groupC']]);
	}

	public function testUnassignUserFromGroups(): void {
		$groupManager = $this->getRealGroupManager();
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$groupA->method('getBackendNames')
			->willReturn(['Database', 'user_saml']);
		$this->groupManager
			->method('get')
			->with('groupA')
			->willReturn($groupA);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$groupA->expects($this->exactly(2))
			->method('getGID')
			->willReturn('gid');
		// assert membership gets removed
		$this->ownGroupBackend
			->expects($this->once())
			->method('removeFromGroup');
		// assert no remaining group memberships
		$this->ownGroupBackend
			->expects($this->once())
			->method('countUsersInGroup')
			->willReturn(0);
		// assert group is deleted
		$this->ownGroupBackend
			->expects($this->once())
			->method('deleteGroup');
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use ($groupA): void {
				static $i = 0;
				match (++$i) {
					1 => $this->assertEquals(new BeforeGroupDeletedEvent($groupA), $event),
					2 => $this->assertEquals(new GroupDeletedEvent($groupA), $event),
					default => $this->fail(),
				};
			});

		$this->invokePrivate($groupManager, 'handleUserUnassignedFromGroups', [$user, ['groupA']]);
	}

	public function testUnassignUserFromGroupsWithKeepEmpytGroups(): void {
		$groupManager = $this->getRealGroupManager();
		// set general-keep_groups to 1 and assert it was read
		$this->appConfig
			->expects($this->exactly(1))
			->method('getAppValueInt')
			->with('general-keep_groups', 0)
			->willReturn(1);
		// create user and group mock
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$groupA->method('getBackendNames')
			->willReturn(['Database', 'user_saml']);
		$this->groupManager
			->method('get')
			->with('groupA')
			->willReturn($groupA);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$groupA->expects($this->exactly(1))
			->method('getGID')
			->willReturn('gid');
		// assert membership gets removed
		$this->ownGroupBackend
			->expects($this->once())
			->method('removeFromGroup');
		// assert no remaining group memberships
		$this->ownGroupBackend
			->expects($this->never())
			->method('countUsersInGroup')
			->willReturn(0);
		// assert group is not deleted
		$this->ownGroupBackend
			->expects($this->never())
			->method('deleteGroup');
		$this->eventDispatcher->expects($this->exactly(0))
			->method('dispatchTyped');

		$this->invokePrivate($groupManager, 'handleUserUnassignedFromGroups', [$user, ['groupA']]);
	}

	public function testAssignUserToGroups(): void {
		$groupManager = $this->getPartialGroupManager(['hasSamlBackend', 'createGroupInBackend']);
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);

		$this->appConfig->expects($this->any())
			->method('getAppValueString')
			->with(GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION)
			->willReturnArgument(1);

		// assert group already exists
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('groupA')
			->willReturn($groupA);
		// assert SAML group backend
		$groupManager
			->expects($this->once())
			->method('hasSamlBackend')
			->willReturn(true);
		$groupA->expects($this->once())
			->method('addUser')
			->with($user);
		$groupManager
			->expects($this->never())
			->method('createGroupInBackend');

		$this->invokePrivate($groupManager, 'handleUserAssignedToGroups', [$user, ['groupA']]);
	}

	public function testAssignUserToNonExistingGroups(): void {
		$groupManager = $this->getRealGroupManager();
		$user = $this->createMock(IUser::class);
		$groupB = $this->createMock(IGroup::class);

		$this->appConfig->expects($this->any())
			->method('getAppValueString')
			->with(GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION)
			->willReturnArgument(1);

		// assert group does not exist
		$this->groupManager
			->method('get')
			->willReturnOnConsecutiveCalls(null, $groupB);
		// assert group is created
		$this->ownGroupBackend
			->expects($this->once())
			->method('createGroup')
			->with('SAML_groupB', 'groupB')
			->willReturn(true);
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use ($groupB): void {
				static $i = 0;
				match (++$i) {
					1 => $this->assertEquals(new BeforeGroupCreatedEvent('SAML_groupB'), $event),
					2 => $this->assertEquals(new GroupCreatedEvent($groupB), $event),
					default => $this->fail(),
				};
			});
		// assert user gets added to group
		$groupB->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($groupManager, 'handleUserAssignedToGroups', [$user, ['groupB']]);
	}

	public function testAssignUserToGroupsWithCollision(): void {
		$groupManager = $this->getPartialGroupManager(['hasSamlBackend']);
		$user = $this->createMock(IUser::class);
		$groupC = $this->createMock(IGroup::class);

		$this->appConfig->expects($this->any())
			->method('getAppValueString')
			->with(GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION)
			->willReturnArgument(1);

		// assert group exists
		$this->groupManager
			->method('get')
			->willReturnCallback(fn (string $groupId): ?IGroup => match ($groupId) {
				'groupC' => $groupC,
				'SAML_groupC' => $groupC,
				default => null,
			});
		// assert differnt group backend
		$groupManager
			->expects($this->once())
			->method('hasSamlBackend')
			->willReturn(false);
		// assert there is only one idp config present
		$this->settings
			->expects($this->once())
			->method('getProviderId');
		// assert the default group prefix is configured
		$this->settings
			->method('get');
		// assert group is created with prefix + gid
		$this->ownGroupBackend
			->expects($this->once())
			->method('createGroup')
			->with('SAML_groupC', 'groupC')
			->willReturn(true);
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use ($groupC): void {
				static $i = 0;
				match (++$i) {
					1 => $this->assertEquals(new BeforeGroupCreatedEvent('SAML_groupC'), $event),
					2 => $this->assertEquals(new GroupCreatedEvent($groupC), $event),
					default => $this->fail(),
				};
			});
		// assert user gets added to group
		$groupC->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($groupManager, 'handleUserAssignedToGroups', [$user, ['groupC']]);
	}
}
