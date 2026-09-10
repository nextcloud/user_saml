<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\User_SAML\GroupBackend;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class GroupBackendTest extends TestCase {
	private GroupBackend $groupBackend;
	private IDBConnection $connection;
	private array $users = [
		[
			'uid' => 'user_saml_integration_test_uid1',
			'displayname' => 'SAML Integration User One',
			'email' => 'saml-integration-one@example.test',
			'groups' => [
				'user_saml_integration_test_gid1',
				'SAML_user_saml_integration_test_gid2'
			]
		],
		[
			'uid' => 'user_saml_integration_test_uid2',
			'displayname' => 'SAML Integration User Two',
			'email' => 'saml-integration-two@example.test',
			'groups' => [
				'user_saml_integration_test_gid1'
			]
		]
	];
	private array $groups = [
		[
			'gid' => 'user_saml_integration_test_gid1',
			'saml_gid' => 'user_saml_integration_test_gid1',
			'members' => [
				'user_saml_integration_test_uid1',
				'user_saml_integration_test_uid2'
			],
			'saml_gid_exists' => true
		],
		[
			'gid' => 'SAML_user_saml_integration_test_gid2',
			'saml_gid' => 'user_saml_integration_test_gid2',
			'members' => [
				'user_saml_integration_test_uid1'
			],
			'saml_gid_exists' => false
		],
		[
			'gid' => 'user_saml_integration_test_gid3',
			'saml_gid' => 'user_saml_integration_test_gid3',
			'members' => [],
			'saml_gid_exists' => true
		],
	];

	#[Override]
	public function setUp(): void {
		parent::setUp();
		$this->connection = \OCP\Server::get(IDBConnection::class);
		$this->resetAccountData();
		$this->groupBackend = new GroupBackend($this->connection, $this->createMock(LoggerInterface::class));
		foreach ($this->groups as $group) {
			$this->groupBackend->createGroup($group['gid'], $group['saml_gid']);
		}
		foreach ($this->users as $user) {
			foreach ($user['groups'] as $group) {
				$this->groupBackend->addToGroup($user['uid'], $group);
			}
			$this->setAccountData($user['uid'], 'displayname', $user['displayname']);
			$this->setAccountData($user['uid'], 'email', $user['email']);
		}
	}

	#[Override]
	public function tearDown(): void {
		parent::tearDown();
		$this->groupBackend = new GroupBackend($this->connection, $this->createMock(LoggerInterface::class));
		foreach ($this->users as $user) {
			foreach ($user['groups'] as $group) {
				$this->groupBackend->removeFromGroup($user['uid'], $group);
			}
		}
		foreach ($this->groups as $group) {
			$this->groupBackend->deleteGroup($group['gid']);
		}
		$this->resetAccountData();
	}

	public function testInGroup(): void {
		foreach ($this->groups as $group) {
			foreach ($this->users as $user) {
				$result = $this->groupBackend->inGroup($user['uid'], $group['gid']);
				if (in_array($group['gid'], $user['groups'])) {
					$this->assertTrue($result, sprintf('User %s should be member of group %s', $user['uid'], $group['gid']));
				} else {
					$this->assertFalse($result, sprintf('User %s should not be member of group %s', $user['uid'], $group['gid']));
				}
			}
		}
	}

	public function testGetGroups(): void {
		$groups = $this->groupBackend->getGroups();
		foreach ($this->groups as $group) {
			$this->assertContains($group['gid'], $groups, sprintf('Group %s should be retrieved', $group['gid']));
		}
	}

	public function testGetUserGroups(): void {
		foreach ($this->users as $user) {
			$userGroups = $this->groupBackend->getUserGroups($user['uid']);
			$this->assertCount(count($user['groups']), $userGroups, 'Should retrieve all user groups');
			foreach ($userGroups as $userGroup) {
				$this->assertContains($userGroup, $user['groups'], sprintf('Users %s should be member of groups %s', $user['uid'], $userGroup));
			}
		}
	}

	public function testGroupExists(): void {
		foreach ($this->groups as $group) {
			$result = $this->groupBackend->groupExists($group['saml_gid']);
			$this->assertSame($group['saml_gid_exists'], $result, sprintf('Group %s should exist', $group['saml_gid']));
		}
	}

	public function testUsersInGroups(): void {
		foreach ($this->groups as $group) {
			$users = $this->groupBackend->usersInGroup($group['gid']);
			$this->assertCount(count($group['members']), $users, 'Should retrieve all group members');
			foreach ($users as $user) {
				$this->assertContains($user, $group['members'], sprintf('User %s should be member of group %s', $user, $group['gid']));
			}
		}
	}

	public function testUsersInGroupMatchesDisplayNameAndEmail(): void {
		$groupId = $this->groups[0]['gid'];

		$byDisplayName = $this->groupBackend->usersInGroup($groupId, $this->users[0]['displayname']);
		$this->assertContains($this->users[0]['uid'], $byDisplayName, 'Display name search should return the matching user');

		$byEmail = $this->groupBackend->usersInGroup($groupId, $this->users[1]['email']);
		$this->assertContains($this->users[1]['uid'], $byEmail, 'Email search should return the matching user');

		$byUid = $this->groupBackend->usersInGroup($groupId, $this->users[0]['uid']);
		$this->assertContains($this->users[0]['uid'], $byUid, 'UID search should still work');
	}

	public function testCountUsersInGroupMatchesDisplayNameAndEmail(): void {
		$groupId = $this->groups[0]['gid'];

		$this->assertSame(1, $this->groupBackend->countUsersInGroup($groupId, $this->users[0]['displayname']));
		$this->assertSame(1, $this->groupBackend->countUsersInGroup($groupId, $this->users[1]['email']));
		$this->assertSame(1, $this->groupBackend->countUsersInGroup($groupId, $this->users[0]['uid']));
	}

	public function testSearchInGroup(): void {
		foreach ($this->groups as $group) {
			$users = $this->groupBackend->searchInGroup($group['gid']);
			$this->assertCount(count($group['members']), $users, 'Should retrieve all group members');
			foreach (array_keys($users) as $uid) {
				$this->assertContains($uid, $group['members'], sprintf('User %s should be member of group %s', $uid, $group['gid']));
			}
		}
	}

	public function testSearchInGroupMatchesDisplayNameAndEmail(): void {
		$groupId = $this->groups[0]['gid'];

		$byDisplayName = $this->groupBackend->searchInGroup($groupId, $this->users[0]['displayname']);
		$this->assertArrayHasKey($this->users[0]['uid'], $byDisplayName, 'Display name search should return the matching user');

		$byEmail = $this->groupBackend->searchInGroup($groupId, $this->users[1]['email']);
		$this->assertArrayHasKey($this->users[1]['uid'], $byEmail, 'Email search should return the matching user');

		$byUid = $this->groupBackend->searchInGroup($groupId, $this->users[0]['uid']);
		$this->assertArrayHasKey($this->users[0]['uid'], $byUid, 'UID search should still work');
	}

	public function testGetBackendName(): void {
		$this->assertSame('user_saml', $this->groupBackend->getBackendName());
	}

	public function testGetDisplayName(): void {
		$group = $this->groups[0];
		$this->assertSame($group['saml_gid'], $this->groupBackend->getDisplayName($group['gid']));

		// falls back to the gid itself when the group is unknown
		$this->assertSame('unknown_gid', $this->groupBackend->getDisplayName('unknown_gid'));
	}

	public function testSetDisplayName(): void {
		$group = $this->groups[2];
		$this->assertTrue($this->groupBackend->setDisplayName($group['gid'], 'New Display Name'));
		$this->assertSame('New Display Name', $this->groupBackend->getDisplayName($group['gid']));

		$this->assertFalse($this->groupBackend->setDisplayName('unknown_gid', 'New Display Name'));
	}

	public function testGroupsExists(): void {
		$gids = array_column($this->groups, 'gid');
		$result = $this->groupBackend->groupsExists([...$gids, 'unknown_gid']);

		$this->assertCount(count($gids), $result);
		foreach ($gids as $gid) {
			$this->assertContains($gid, $result);
		}
	}

	public function testGetGroupDetails(): void {
		$group = $this->groups[0];
		$this->assertSame(['displayName' => $group['saml_gid']], $this->groupBackend->getGroupDetails($group['gid']));
		$this->assertSame([], $this->groupBackend->getGroupDetails('unknown_gid'));
	}

	public function testGetGroupsDetails(): void {
		$group = $this->groups[0];
		$result = $this->groupBackend->getGroupsDetails([$group['gid'], 'unknown_gid']);

		$this->assertSame(['displayName' => $group['saml_gid']], $result[$group['gid']]);
		$this->assertArrayNotHasKey('unknown_gid', $result);
	}

	public function testAddToGroupIsIdempotent(): void {
		$group = $this->groups[2];
		$uid = $this->users[0]['uid'];
		$this->assertFalse($this->groupBackend->inGroup($uid, $group['gid']));

		$this->assertTrue($this->groupBackend->addToGroup($uid, $group['gid']));
		$this->assertTrue($this->groupBackend->inGroup($uid, $group['gid']));

		// adding an already-member user is a no-op that still reports success
		$this->assertTrue($this->groupBackend->addToGroup($uid, $group['gid']));

		$this->groupBackend->removeFromGroup($uid, $group['gid']);
	}

	public function testRemoveFromGroupReturnsFalseWhenNotMember(): void {
		$group = $this->groups[2];
		$uid = $this->users[0]['uid'];

		$this->assertFalse($this->groupBackend->removeFromGroup($uid, $group['gid']));

		$this->groupBackend->addToGroup($uid, $group['gid']);
		$this->assertTrue($this->groupBackend->removeFromGroup($uid, $group['gid']));
	}

	public function testCreateGroupReturnsNullOnDuplicate(): void {
		$existingGroup = $this->groups[0];
		$this->assertNull($this->groupBackend->createGroup($existingGroup['gid']));
	}

	public function testDeleteGroup(): void {
		$gid = $this->groupBackend->createGroup('user_saml_integration_test_throwaway_group');
		$this->assertNotNull($gid);
		$this->assertTrue($this->groupBackend->groupExists($gid));

		$this->assertTrue($this->groupBackend->deleteGroup($gid));
		$this->assertFalse($this->groupBackend->groupExists($gid));

		// deleting an already-deleted group still reports success
		$this->assertTrue($this->groupBackend->deleteGroup($gid));
	}

	private function resetAccountData(): void {
		foreach ($this->users as $user) {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('accounts_data')
				->where($qb->expr()->eq('uid', $qb->createNamedParameter($user['uid'])))
				->executeStatement();
		}
	}

	private function setAccountData(string $uid, string $name, string $value): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('accounts_data')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($name)))
			->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('accounts_data')
			->setValue('uid', $qb->createNamedParameter($uid))
			->setValue('name', $qb->createNamedParameter($name))
			->setValue('value', $qb->createNamedParameter($value))
			->executeStatement();
	}
}
