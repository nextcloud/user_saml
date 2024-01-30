<?php
/**
 * @copyright Copyright (c) 2021 Giuliano Mele <giuliano.mele@verdigado.com>
 *
 * @author Giuliano Mele <giuliano.mele@verdigado.com>
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

namespace OCA\User_SAML\Tests;

use OC\BackgroundJob\JobList;
use OC\Group\Manager;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\SAMLSettings;
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
			->willReturnCallback(function ($groupId) use ($groupA, $groupB): ?IGroup {
				switch ($groupId) {
					case 'groupA':
						return $groupA;
					case 'groupB':
						return $groupB;
					default:
						return null;
				}
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
			->willReturnCallback(function ($groupId) use ($groupC) {
				switch ($groupId) {
					case 'groupC':
						return $groupC;
					case 'SAML_groupC':
						return $groupC;
				}
				return null;
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
		// assert user gets added to group
		$groupC->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($this->ownGroupManager, 'handleUserAssignedToGroups', [$user, ['groupC']]);
	}
}
