<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OC\BackgroundJob\JobList;
use OC\Group\Manager;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\SAMLSettings;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroupManagerTest extends TestCase {

	/** @var IDBConnection|MockObject */
	private $db;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var GroupBackend|MockObject */
	private $ownGroupBackend;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;
	/** @var JobList|MockObject */
	private $jobList;
	/** @var SAMLSettings|MockObject */
	private $settings;
	/** @var GroupManager|MockObject */
	private $ownGroupManager;

	protected function setUp(): void {
		parent::setUp();
		$this->db = $this->createMock(IDBConnection::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->ownGroupBackend = $this->createMock(GroupBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->jobList = $this->createMock(JobList::class);
		$this->settings = $this->createMock(SAMLSettings::class);
		$this->ownGroupManager = $this->createMock(GroupManager::class);
	}

	public function getGroupManager(array $mockedFunctions = []) {
		if (!empty($mockedFunctions)) {
			$this->ownGroupManager = $this->getMockBuilder(GroupManager::class)
				->setConstructorArgs([
					$this->db,
					$this->groupManager,
					$this->ownGroupBackend,
					$this->config,
					$this->eventDispatcher,
					$this->jobList,
					$this->settings
				])
				->onlyMethods($mockedFunctions)
				->getMock();
		} else {
			$this->ownGroupManager = new GroupManager(
				$this->db,
				$this->groupManager,
				$this->ownGroupBackend,
				$this->config,
				$this->eventDispatcher,
				$this->jobList,
				$this->settings
			);
		}
	}

	public function testUpdateUserGroups() {
		// Case: The known memberships of the user are groupA and groupB. The new
		// memberships are GroupB and GroupC. Hence, the user must be unassigned
		// from GroupA and assigned to GroupC, while the GroupB association remains
		// unchanged.

		$this->config->expects($this->any())
			->method('getAppValue')
			->with('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '')
			->willReturn(\json_encode(['groups' => ['groupA'], 'dropAfter' => time() + 2000]));

		$this->getGroupManager(['handleUserUnassignedFromGroups', 'handleUserAssignedToGroups', 'translateGroupToIds', 'hasSamlBackend']);
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
		$this->ownGroupManager
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
			->willReturnCallback(fn ($groupId): ?IGroup => match ($groupId) {
				'groupA' => $groupA,
				'groupB' => $groupB,
				default => null,
			});
		// assert all groups are supplied by SAML backend
		$this->ownGroupManager
			->method('hasSamlBackend')
			->willReturn(true);
		// assert removing membership to groupA
		$this->ownGroupManager
			->expects($this->once())
			->method('handleUserUnassignedFromGroups')
			->with($user, ['groupA']);
		// assert adding membership to groupC
		$this->ownGroupManager
			->expects($this->once())
			->method('handleUserAssignedToGroups')
			->with($user, ['groupC']);

		// assert SAML provides user groups groupB and groupC
		$this->invokePrivate($this->ownGroupManager, 'updateUserGroups', [$user, ['groupB', 'groupC']]);
	}

	public function testUnassignUserFromGroups() {
		$this->getGroupManager();
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
			->withConsecutive(
				[new BeforeGroupDeletedEvent($groupA)],
				[new GroupDeletedEvent($groupA)]
			);

		$this->invokePrivate($this->ownGroupManager, 'handleUserUnassignedFromGroups', [$user, ['groupA']]);
	}

	public function testUnassignUserFromGroupsWithKeepEmpytGroups() {
		$this->getGroupManager();
		// set general-keep_groups to 1 and assert it was read
		$this->config
			->expects($this->exactly(1))
			->method('getAppValue')
			->with('user_saml', 'general-keep_groups', '0')
			->willReturn('1');
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

		$this->invokePrivate($this->ownGroupManager, 'handleUserUnassignedFromGroups', [$user, ['groupA']]);
	}

	public function testAssignUserToGroups() {
		$this->getGroupManager(['hasSamlBackend', 'createGroupInBackend']);
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);

		$this->config->expects($this->any())
			->method('getAppValue')
			->with('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '')
			->willReturnArgument(2);

		// assert group already exists
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('groupA')
			->willReturn($groupA);
		// assert SAML group backend
		$this->ownGroupManager
			->expects($this->once())
			->method('hasSamlBackend')
			->willReturn(true);
		$groupA->expects($this->once())
			->method('addUser')
			->with($user);
		$this->ownGroupManager
			->expects($this->never())
			->method('createGroupInBackend');

		$this->invokePrivate($this->ownGroupManager, 'handleUserAssignedToGroups', [$user, ['groupA']]);
	}

	public function testAssignUserToNonExistingGroups() {
		$this->getGroupManager();
		$user = $this->createMock(IUser::class);
		$groupB = $this->createMock(IGroup::class);

		$this->config->expects($this->any())
			->method('getAppValue')
			->with('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '')
			->willReturnArgument(2);

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
			->withConsecutive(
				[new BeforeGroupCreatedEvent('SAML_groupB')],
				[new GroupCreatedEvent($groupB)]
			);
		// assert user gets added to group
		$groupB->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($this->ownGroupManager, 'handleUserAssignedToGroups', [$user, ['groupB']]);
	}

	public function testAssignUserToGroupsWithCollision() {
		$this->getGroupManager(['hasSamlBackend']);
		$user = $this->createMock(IUser::class);
		$groupC = $this->createMock(IGroup::class);

		$this->config->expects($this->any())
			->method('getAppValue')
			->with('user_saml', GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '')
			->willReturnArgument(2);

		// assert group exists
		$this->groupManager
			->method('get')
			->willReturnCallback(fn ($groupId) => match ($groupId) {
				'groupC' => $groupC,
				'SAML_groupC' => $groupC,
				default => null,
			});
		// assert differnt group backend
		$this->ownGroupManager
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
			->withConsecutive(
				[new BeforeGroupCreatedEvent('SAML_groupC')],
				[new GroupCreatedEvent($groupC)]
			);
		// assert user gets added to group
		$groupC->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($this->ownGroupManager, 'handleUserAssignedToGroups', [$user, ['groupC']]);
	}
}
