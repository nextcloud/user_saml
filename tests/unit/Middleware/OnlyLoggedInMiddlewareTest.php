<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Middleware;

use Exception;
use OCA\User_SAML\Middleware\OnlyLoggedInMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

class OnlyLoggedInMiddlewareTest extends \Test\TestCase {
	protected IURLGenerator&MockObject $urlGenerator;
	private IUserSession&MockObject $userSession;
	private OnlyLoggedInMiddleware&MockObject $onlyLoggedInMiddleware;

	#[Override]
	protected function setUp(): void {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->onlyLoggedInMiddleware = $this->getMockBuilder(OnlyLoggedInMiddleware::class)
			->setConstructorArgs([
				$this->userSession,
				$this->urlGenerator,
			])
			->onlyMethods(['hasAttribute'])
			->getMock();

		parent::setUp();
	}

	public function testBeforeControllerWithoutAnnotation(): void {
		$controller = $this->createMock(Controller::class);
		$this->onlyLoggedInMiddleware
			->expects($this->once())
			->method('hasAttribute')
			->with($controller, 'bar')
			->willReturn(false);
		$this->userSession
			->expects($this->never())
			->method('isLoggedIn');

		$this->onlyLoggedInMiddleware->beforeController($controller, 'bar');
	}

	public function testBeforeControllerWithAnnotationAndNotLoggedIn(): void {
		$controller = $this->createMock(Controller::class);
		$this->onlyLoggedInMiddleware
			->expects($this->once())
			->method('hasAttribute')
			->with($controller, 'bar')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->onlyLoggedInMiddleware->beforeController($controller, 'bar');
	}

	public function testBeforeControllerWithAnnotationAndLoggedIn(): void {
		$controller = $this->createMock(Controller::class);
		$this->onlyLoggedInMiddleware
			->expects($this->once())
			->method('hasAttribute')
			->with($controller, 'bar')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('User is already logged-in');

		$this->onlyLoggedInMiddleware->beforeController($controller, 'bar');
	}

	public function testAfterExceptionWithNormalException(): void {
		$exceptionMsg = 'My Exception';
		$exception = new Exception($exceptionMsg);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage($exceptionMsg);

		$this->onlyLoggedInMiddleware->afterException($this->createMock(Controller::class), 'bar', $exception);
	}

	public function testAfterExceptionWithAlreadyLoggedInException(): void {
		$homeUrl = 'https://my.nxt.cld/';
		$this->urlGenerator->expects($this->atLeastOnce())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn($homeUrl);

		$exception = new Exception('User is already logged-in');
		$expected = new RedirectResponse($homeUrl);
		$this->assertEquals($expected, $this->onlyLoggedInMiddleware->afterException($this->createMock(Controller::class), 'bar', $exception));
	}
}
