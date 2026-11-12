<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\Exceptions\UserFilterViolationException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCP\IConfig;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class DavPluginTest extends TestCase {
	/** @var ISession|MockObject */
	private $session;
	/** @var UserData|MockObject */
	private $userData;
	/** @var UserBackend|MockObject */
	private $userBackend;
	private DavPlugin $davPlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->userData = $this->createMock(UserData::class);
		$this->userBackend = $this->createMock(UserBackend::class);

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$samlSettings = $this->createMock(SAMLSettings::class);
		$samlSettings->method('get')
			->with(1)
			->willReturn(['general-uid_mapping' => 'REMOTE_USER']);

		$this->davPlugin = new DavPlugin(
			$this->session,
			$config,
			['REMOTE_USER' => 'alice'],
			$samlSettings,
			$this->userData,
			$this->userBackend,
		);
	}

	public function testBeforeMethodPreparesSession(): void {
		$this->userData->expects($this->once())
			->method('setAttributes')
			->with(['REMOTE_USER' => 'alice']);
		$this->userBackend->expects($this->once())
			->method('assertGroupMemberships');
		$calls = [];
		$this->session->expects($this->exactly(2))
			->method('set')
			->willReturnCallback(function (string $key, $value) use (&$calls): void {
				$calls[$key] = $value;
			});

		$this->davPlugin->beforeMethod($this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));

		$this->assertSame(['REMOTE_USER' => 'alice'], $calls['user_saml.samlUserData']);
	}

	public function testBeforeMethodRespectsUserFilter(): void {
		$this->userBackend->expects($this->once())
			->method('assertGroupMemberships')
			->willThrowException(new UserFilterViolationException('User is member of a rejection group.'));
		$this->session->expects($this->never())
			->method('set');

		$this->davPlugin->beforeMethod($this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
	}
}
