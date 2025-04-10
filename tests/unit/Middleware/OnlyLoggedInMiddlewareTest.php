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
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IURLGenerator;
use OCP\IUserSession;

class OnlyLoggedInMiddlewareTest extends \Test\TestCase {
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;
	/** @var IControllerMethodReflector|\PHPUnit_Framework_MockObject_MockObject */
	private $reflector;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var OnlyLoggedInMiddleware */
	private $onlyLoggedInMiddleware;

	protected function setUp(): void {
		$this->reflector = $this->createMock(IControllerMethodReflector::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->onlyLoggedInMiddleware = new OnlyLoggedInMiddleware(
			$this->reflector,
			$this->userSession,
			$this->urlGenerator
		);

		parent::setUp();
	}

	public function testBeforeControllerWithoutAnnotation() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('OnlyUnauthenticatedUsers')
			->willReturn(false);
		$this->userSession
			->expects($this->never())
			->method('isLoggedIn');

		$this->onlyLoggedInMiddleware->beforeController($this->createMock(Controller::class), 'bar');
	}

	public function testBeforeControllerWithAnnotationAndNotLoggedIn() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('OnlyUnauthenticatedUsers')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->onlyLoggedInMiddleware->beforeController($this->createMock(Controller::class), 'bar');
	}

	public function testBeforeControllerWithAnnotationAndLoggedIn() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('OnlyUnauthenticatedUsers')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('User is already logged-in');

		$this->onlyLoggedInMiddleware->beforeController($this->createMock(Controller::class), 'bar');
	}

	public function testAfterExceptionWithNormalException() {
		$exceptionMsg = 'My Exception';
		$exception = new Exception($exceptionMsg);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage($exceptionMsg);

		$this->onlyLoggedInMiddleware->afterException($this->createMock(Controller::class), 'bar', $exception);
	}

	public function testAfterExceptionWithAlreadyLoggedInException() {
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
