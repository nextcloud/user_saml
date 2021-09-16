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
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\GroupDuplicateChecker;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\SAMLSettings;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class GroupManagerTest extends TestCase {

	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $db;
	/** @var GroupDuplicateChecker|\PHPUnit_Framework_MockObject_MockObject */
	private $duplicateChecker;
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var GroupBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $ownGroupBackend;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var JobList|\PHPUnit_Framework_MockObject_MockObject */
	private $jobList;
	/** @var SAMLSettings|\PHPUnit_Framework_MockObject_MockObject */
	private $settings;
	/** @var GroupManager|\PHPUnit_Framework_MockObject_MockObject */
	private $ownGroupManager;

	protected function setUp(): void {
		parent::setUp();
		$this->db = $this->createMock(IDBConnection::class);
		$this->duplicateChecker = $this->createMock(GroupDuplicateChecker::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
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
					$this->duplicateChecker,
					$this->groupManager,
					$this->userManager,
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
				$this->duplicateChecker,
				$this->groupManager,
				$this->userManager,
				$this->ownGroupBackend,
				$this->config,
				$this->jobList,
				$this->settings
			);
		}
	}

	public function testReplaceGroups() {
		$this->getGroupManager(['removeGroups', 'addGroups', 'translateGroupToIds', 'hasSamlBackend']);
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$groupB = $this->createMock(IGroup::class);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
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
		$groupA->method('getGID')
			->willReturn('groupA');
		$groupB->method('getGID')
			->willReturn('groupB');
		$this->groupManager
			->method('get')
			->with('groupB')
			->willReturn($groupB);
		// assert all groups are supplied by SAML backend
		$this->ownGroupManager
			->method('hasSamlBackend')
			->willReturn(true);
		// assert removing membership to groupA
		$this->ownGroupManager
			->expects($this->once())
			->method('removeGroups')
			->with($user, ['groupA']);
		// assert adding membership to groupC
		$this->ownGroupManager
			->expects($this->once())
			->method('addGroups')
			->with($user, ['groupC']);
		
		// assert SAML provides user groups groupB and groupC
		$this->ownGroupManager->replaceGroups('ExistingUser', ['groupB', 'groupC']);
	}

	public function testRemoveGroups() {
		$this->getGroupManager();
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);
		$this->groupManager
			->expects($this->at(0))
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

		$this->ownGroupManager->removeGroups($user, ['groupA']);
	}

	public function testAddToExistingGroup() {
		$this->getGroupManager(['hasSamlBackend', 'createGroupInBackend']);
		$user = $this->createMock(IUser::class);
		$groupA = $this->createMock(IGroup::class);

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
		
		$this->ownGroupManager->addGroups($user, ['groupA']);
	}

	public function testAddToNonExistingGroups() {
		$this->getGroupManager();
		$user = $this->createMock(IUser::class);
		$groupB = $this->createMock(IGroup::class);
		
		// assert group does not exist
		$this->groupManager
			->expects($this->at(0))
			->method('get')
			->with('groupB')
			->willReturn(null);
		// assert group is created
		$this->ownGroupBackend
			->expects($this->once())
			->method('createGroup')
			->with('groupB', 'groupB')
			->willReturn(true);
		$this->groupManager
			->expects($this->at(1))
			->method('get')
			->with('groupB')
			->willReturn($groupB);
		// assert user gets added to group
		$groupB->expects($this->once())
			->method('addUser')
			->with($user);

		$this->ownGroupManager->addGroups($user, ['groupB']);
	}

	public function testAddGroupsWithCollision() {
		$this->getGroupManager(['hasSamlBackend']);
		$user = $this->createMock(IUser::class);
		$groupC = $this->createMock(IGroup::class);

		// assert group exists
		$this->groupManager
			->expects($this->at(0))
			->method('get')
			->with('groupC')
			->willReturn($groupC);
		// assert differnt group backend
		$this->ownGroupManager
			->expects($this->once())
			->method('hasSamlBackend')
			->willReturn(false);
		// assert there is only one idp config present
		$this->settings
			->expects($this->once())
			->method('getPrefix')
			->willReturn('');
		// assert the default group prefix is configured
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'saml-attribute-mapping-group_mapping_prefix', 'SAML_')
			->willReturn('SAML_');
		// assert group is created with prefix + gid
		$this->ownGroupBackend
			->expects($this->once())
			->method('createGroup')
			->with('SAML_groupC', 'groupC')
			->willReturn(true);
		$this->groupManager
			->expects($this->at(1))
			->method('get')
			->with('SAML_groupC')
			->willReturn($groupC);
		// assert user gets added to group
		$groupC->expects($this->once())
			->method('addUser')
			->with($user);

		$this->ownGroupManager->addGroups($user, ['groupC']);
	}

}
