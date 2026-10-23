<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\DavPlugin;
use OCA\User_SAML\Exceptions\UserFilterViolationException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\Service\SessionService;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCP\AppFramework\Services\IAppConfig;
use OCP\ISession;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class DavPluginTest extends TestCase {
	private ISession&MockObject $session;
	private SessionService&MockObject $sessionService;
	private UserData&MockObject $userData;
	private UserBackend&MockObject $userBackend;
	private DavPlugin $davPlugin;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->sessionService = $this->createMock(SessionService::class);
		$this->userData = $this->createMock(UserData::class);
		$this->userBackend = $this->createMock(UserBackend::class);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueString')
			->with('type')
			->willReturn('environment-variable');
		$samlSettings = $this->createMock(SAMLSettings::class);
		$samlSettings->method('get')
			->with(1)
			->willReturn(['general-uid_mapping' => 'REMOTE_USER']);

		$this->davPlugin = new DavPlugin(
			$this->session,
			$appConfig,
			['REMOTE_USER' => 'alice'],
			$samlSettings,
			$this->sessionService,
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
		$this->session->expects($this->once())
			->method('set');
		$this->sessionService->expects($this->once())
			->method('prepareEnvironmentBasedSession')
			->with(['REMOTE_USER' => 'alice']);

		$this->davPlugin->beforeMethod($this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
	}

	public function testBeforeMethodRespectsUserFilter(): void {
		$this->userBackend->expects($this->once())
			->method('assertGroupMemberships')
			->willThrowException(new UserFilterViolationException('User is member of a rejection group.'));
		$this->session->expects($this->never())
			->method('set');
		$this->sessionService->expects($this->never())
			->method('prepareEnvironmentBasedSession');

		$this->davPlugin->beforeMethod($this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
	}
}
