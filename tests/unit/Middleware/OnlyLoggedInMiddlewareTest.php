<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\User_OIDC\Tests\Middleware;

use OCA\User_OIDC\Middleware\OnlyLoggedInMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IUserSession;

class OnlyLoggedInMiddlewareTest extends \Test\TestCase  {
	/** @var IControllerMethodReflector|\PHPUnit_Framework_MockObject_MockObject */
	private $reflector;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var OnlyLoggedInMiddleware */
	private $onlyLoggedInMiddleware;

	public function setUp() {
		$this->reflector = $this->createMock(IControllerMethodReflector::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->onlyLoggedInMiddleware = new OnlyLoggedInMiddleware(
			$this->reflector,
			$this->userSession
		);

		return parent::setUp();
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

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User is already logged-in
	 */
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

		$this->onlyLoggedInMiddleware->beforeController($this->createMock(Controller::class), 'bar');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage My Exception
	 */
	public function testAfterExceptionWithNormalException() {
		$exception = new \Exception('My Exception');
		$this->onlyLoggedInMiddleware->afterException($this->createMock(Controller::class), 'bar', $exception);
	}

	public function testAfterExceptionWithAlreadyLoggedInException() {
		$exception = new \Exception('User is already logged-in');
		$expected = new JSONResponse('User is already logged-in', 403);
		$this->assertEquals($expected, $this->onlyLoggedInMiddleware->afterException($this->createMock(Controller::class), 'bar', $exception));
	}
}
