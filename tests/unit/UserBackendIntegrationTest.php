<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\GroupManager;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class UserBackendIntegrationTest extends TestCase {
	private IDBConnection $db;
	private UserBackend $userBackend;

	private array $users = [
		[
			'uid' => 'user_saml_integration_test_uid1',
			'displayname' => 'SAML Integration User One',
			'home' => '/tmp/user_saml_integration_test_uid1',
		],
		[
			'uid' => 'user_saml_integration_test_uid2',
			'displayname' => 'SAML Integration User Two',
			'home' => '',
		],
	];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->db = \OCP\Server::get(IDBConnection::class);
		$this->cleanupUsers();
		$this->cleanupKnownUsers();

		foreach ($this->users as $user) {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('user_saml_users')
				->setValue('uid', $qb->createNamedParameter($user['uid']))
				->setValue('displayname', $qb->createNamedParameter($user['displayname']))
				->setValue('home', $qb->createNamedParameter($user['home']))
				->executeStatement();
		}

		$this->userBackend = new UserBackend(
			$this->createMock(IConfig::class),
			$this->createMock(IAppConfig::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(ISession::class),
			$this->db,
			$this->createMock(IUserManager::class),
			$this->createMock(GroupManager::class),
			$this->getMockBuilder(SAMLSettings::class)->disableOriginalConstructor()->getMock(),
			$this->createMock(LoggerInterface::class),
			$this->createMock(UserData::class),
			$this->createMock(IEventDispatcher::class),
			'serverRoot',
		);
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();
		$this->cleanupUsers();
		$this->cleanupKnownUsers();
	}

	private function cleanupUsers(): void {
		foreach ($this->users as $user) {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('user_saml_users')
				->where($qb->expr()->eq('uid', $qb->createNamedParameter($user['uid'])))
				->executeStatement();
		}
	}

	private function cleanupKnownUsers(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('known_users')
			->where($qb->expr()->eq('known_to', $qb->createNamedParameter('user_saml_integration_test_searcher')))
			->executeStatement();
	}

	public function testUserExists(): void {
		$this->assertTrue($this->userBackend->userExists('user_saml_integration_test_uid1'));
		$this->assertFalse($this->userBackend->userExists('user_saml_integration_test_nonexistent'));
	}

	public function testGetHome(): void {
		$this->assertSame('/tmp/user_saml_integration_test_uid1', $this->userBackend->getHome('user_saml_integration_test_uid1'));
		$this->assertFalse($this->userBackend->getHome('user_saml_integration_test_nonexistent'));
	}

	public function testGetDisplayName(): void {
		$this->assertSame('SAML Integration User One', $this->userBackend->getDisplayName('user_saml_integration_test_uid1'));
		// falls back to the uid itself when no row exists
		$this->assertSame('user_saml_integration_test_nonexistent', $this->userBackend->getDisplayName('user_saml_integration_test_nonexistent'));
	}

	public function testGetDisplayNames(): void {
		$displayNames = $this->userBackend->getDisplayNames('SAML Integration User');
		$this->assertSame('SAML Integration User One', $displayNames['user_saml_integration_test_uid1']);
		$this->assertSame('SAML Integration User Two', $displayNames['user_saml_integration_test_uid2']);
	}

	public function testGetUsers(): void {
		$users = $this->userBackend->getUsers('user_saml_integration_test');
		$this->assertContains('user_saml_integration_test_uid1', $users);
		$this->assertContains('user_saml_integration_test_uid2', $users);
	}

	public function testSetDisplayName(): void {
		$this->assertTrue($this->userBackend->setDisplayName('user_saml_integration_test_uid1', 'Renamed User'));
		$this->assertSame('Renamed User', $this->userBackend->getDisplayName('user_saml_integration_test_uid1'));

		$this->assertFalse($this->userBackend->setDisplayName('user_saml_integration_test_nonexistent', 'Nope'));
	}

	public function testCountUsers(): void {
		$this->assertSame(count($this->users), $this->userBackend->countUsers());
	}

	public function testDeleteUser(): void {
		$this->assertTrue($this->userBackend->deleteUser('user_saml_integration_test_uid1'));
		$this->assertFalse($this->userBackend->userExists('user_saml_integration_test_uid1'));

		// deleting an already-deleted (or never existing) user reports no rows affected
		$this->assertFalse($this->userBackend->deleteUser('user_saml_integration_test_uid1'));
	}

	public function testSearchKnownUsersByDisplayName(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('known_users')
			->setValue('known_to', $qb->createNamedParameter('user_saml_integration_test_searcher'))
			->setValue('known_user', $qb->createNamedParameter('user_saml_integration_test_uid1'))
			->executeStatement();

		$result = $this->userBackend->searchKnownUsersByDisplayName('user_saml_integration_test_searcher', 'User One');
		$this->assertSame(['user_saml_integration_test_uid1' => 'SAML Integration User One'], $result);

		$result = $this->userBackend->searchKnownUsersByDisplayName('user_saml_integration_test_searcher', 'User Two');
		$this->assertSame([], $result);
	}
}
