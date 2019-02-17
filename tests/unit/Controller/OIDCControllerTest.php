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

namespace OCA\User_OIDC\Tests\Controller;

use OCA\User_OIDC\Controller\OIDCController;
use OCA\User_OIDC\UserBackend;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

class OIDCControllerTest extends TestCase  {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var UserBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var OIDCController */
	private $oidcController;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userBackend = $this->createMock(UserBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->config->expects($this->any())->method('getSystemValue')
			->willReturnCallback(function($key, $default) {
			return $default;
		});

		$this->oidcController = new OIDCController(
			'user_oidc',
			$this->request,
			$this->session,
			$this->userSession,
			$this->userBackend,
			$this->config,
			$this->urlGenerator,
			$this->userManager,
			$this->logger
		);

	}

	public function testNotProvisioned() {
		$expected = new TemplateResponse('user_oidc', 'notProvisioned', [], 'guest');
		$this->assertEquals($expected, $this->oidcController->notProvisioned());
	}

	/**
	 * @dataProvider dataTestGenericError
	 *
	 * @param string $messageSend
	 * @param string $messageExpected
	 */
	public function testGenericError($messageSend, $messageExpected) {
		$expected = new TemplateResponse('user_oidc', 'error', ['message' => $messageExpected], 'guest');
		$this->assertEquals($expected, $this->oidcController->genericError($messageSend));
	}

	public function dataTestGenericError() {
		return [
			['messageSend' => '', 'messageExpected' => 'Unknown error, please check the log file for more details.'],
			['messageSend' => 'test message', 'messageExpected' => 'test message'],
		];
	}

}
