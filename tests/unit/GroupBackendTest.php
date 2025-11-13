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

	/** @var GroupBackend */
	private $groupBackend;
	/** @var IDBConnection */
	private $connection;
	private $users = [
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
	private $groups = [
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

	public function testInGroup() {
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

	public function testGetGroups() {
		$groups = $this->groupBackend->getGroups();
		foreach ($this->groups as $group) {
			$this->assertContains($group['gid'], $groups, sprintf('Group %s should be retrieved', $group['gid']));
		}
	}

	public function testGetUserGroups() {
		foreach ($this->users as $user) {
			$userGroups = $this->groupBackend->getUserGroups($user['uid']);
			$this->assertCount(count($user['groups']), $userGroups, 'Should retrieve all user groups');
			foreach ($userGroups as $userGroup) {
				$this->assertContains($userGroup, $user['groups'], sprintf('Users %s should be member of groups %s', $user['uid'], $userGroup));
			}
		}
	}

	public function testGroupExists() {
		foreach ($this->groups as $group) {
			$result = $this->groupBackend->groupExists($group['saml_gid']);
			$this->assertSame($group['saml_gid_exists'], $result, sprintf('Group %s should exist', $group['saml_gid']));
		}
	}

	public function testUsersInGroups() {
		foreach ($this->groups as $group) {
			$users = $this->groupBackend->usersInGroup($group['gid']);
			$this->assertCount(count($group['members']), $users, 'Should retrieve all group members');
			foreach ($users as $user) {
				$this->assertContains($user, $group['members'], sprintf('User %s should be member of group %s', $user, $group['gid']));
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
