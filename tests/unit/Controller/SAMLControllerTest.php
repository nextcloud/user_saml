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

namespace OCA\User_SAML\Tests\Controller;

use OCA\User_SAML\Controller\SAMLController;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

class SAMLControllerTest extends TestCase  {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var SAMLSettings|\PHPUnit_Framework_MockObject_MockObject*/
	private $samlSettings;
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
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;
	/** @var SAMLController */
	private $samlController;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->userBackend = $this->createMock(UserBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->l = $this->createMock(IL10N::class);

		$this->l->expects($this->any())->method('t')->willReturnCallback(
			function($param) {
				return $param;
			}
		);

		$this->samlController = new SAMLController(
			'user_saml',
			$this->request,
			$this->session,
			$this->userSession,
			$this->samlSettings,
			$this->userBackend,
			$this->config,
			$this->urlGenerator,
			$this->userManager,
			$this->logger,
			$this->l
		);
	}

	/**
	 * @expectedExceptionMessage Type of "UnknownValue" is not supported for user_saml
	 * @expectedException \Exception
	 */
	public function testLoginWithInvalidAppValue() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('UnknownValue');
		$this->samlController->login();
	}

	public function testLoginWithEnvVariableAndNotExistingUidInSettingsArray() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.notProvisioned')
			->willReturn('https://nextcloud.com/notProvisioned/');

		$expected = new RedirectResponse('https://nextcloud.com/notProvisioned/');
		$this->assertEquals($expected, $this->samlController->login());
	}


	public function testLoginWithEnvVariableAndExistingUser() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => 'MyUid',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('MyUid')
			->willReturn(true);
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn('https://nextcloud.com/absolute/');
		$this->userBackend
			->expects($this->once())
			->method('getCurrentUserId')
			->willReturn('MyUid');
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUid')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('updateLastLoginTimestamp');

		$expected = new RedirectResponse('https://nextcloud.com/absolute/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testLoginWithEnvVariableAndExistingUserAndArray() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => ['MyUid'],
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('MyUid')
			->willReturn(true);
		$this->userBackend
			->expects($this->once())
			->method('getCurrentUserId')
			->willReturn('MyUid');
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUid')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('updateLastLoginTimestamp');
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn('https://nextcloud.com/absolute/');

		$expected = new RedirectResponse('https://nextcloud.com/absolute/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testLoginWithEnvVariableAndNotExistingUserWithProvisioning() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => 'MyUid',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('MyUid')
			->willReturn(false);
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn('https://nextcloud.com/absolute/');
		$this->userBackend
			->expects($this->at(0))
			->method('autoprovisionAllowed')
			->willReturn(true);
		$this->userBackend
			->expects($this->at(1))
			->method('createUserIfNotExists')
			->with('MyUid');
		$this->userBackend
			->expects($this->once())
			->method('getCurrentUserId')
			->willReturn('MyUid');
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUid')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('updateLastLoginTimestamp');

		$expected = new RedirectResponse('https://nextcloud.com/absolute/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testLoginWithEnvVariableAndNotExistingUserWithMalfunctioningBackend() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => 'MyUid',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('MyUid')
			->willReturn(false);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.notProvisioned')
			->willReturn('https://nextcloud.com/notprovisioned/');
		$this->userBackend
			->expects($this->at(0))
			->method('autoprovisionAllowed')
			->willReturn(true);
		$this->userBackend
			->expects($this->at(1))
			->method('createUserIfNotExists')
			->with('MyUid');
		$this->userBackend
			->expects($this->once())
			->method('getCurrentUserId')
			->willReturn('MyUid');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUid')
			->willReturn(null);

		$expected = new RedirectResponse('https://nextcloud.com/notprovisioned/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testLoginWithEnvVariableAndNotExistingUserWithoutProvisioning() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => 'MyUid',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->with('MyUid')
			->willReturn(false);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('user_saml.SAML.notProvisioned')
			->willReturn('https://nextcloud.com/notprovisioned/');
		$this->userBackend
			->expects($this->once())
			->method('autoprovisionAllowed')
			->willReturn(false);

		$expected = new RedirectResponse('https://nextcloud.com/notprovisioned/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testLoginWithEnvVariableAndNotYetMappedUserWithoutProvisioning() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('environment-variable');
		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn([
				'foo' => 'bar',
				'uid' => 'MyUid',
				'bar' => 'foo',
			]);
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('user_saml', 'general-uid_mapping')
			->willReturn('uid');
		$this->userManager
			->expects($this->exactly(2))
			->method('userExists')
			->with('MyUid')
			->willReturnOnConsecutiveCalls(false, true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUid')
			->willReturn($this->createMock(IUser::class));
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteUrl')
			->with('/')
			->willReturn('https://nextcloud.com/absolute/');
		$this->urlGenerator
			->expects($this->never())
			->method('linkToRouteAbsolute');
		$this->userBackend
			->expects($this->once())
			->method('autoprovisionAllowed')
			->willReturn(false);
		$this->userBackend
			->expects($this->once())
			->method('getCurrentUserId')
			->willReturn('MyUid');

		$expected = new RedirectResponse('https://nextcloud.com/absolute/');
		$this->assertEquals($expected, $this->samlController->login());
	}

	public function testNotProvisioned() {
		$expected = new TemplateResponse('user_saml', 'notProvisioned', [], 'guest');
		$this->assertEquals($expected, $this->samlController->notProvisioned());
	}

	/**
	 * @dataProvider dataTestGenericError
	 *
	 * @param string $messageSend
	 * @param string $messageExpected
	 */
	public function testGenericError($messageSend, $messageExpected) {
		$expected = new TemplateResponse('user_saml', 'error', ['message' => $messageExpected], 'guest');
		$this->assertEquals($expected, $this->samlController->genericError($messageSend));
	}

	public function dataTestGenericError() {
		return [
			['messageSend' => '', 'messageExpected' => 'Unknown error, please check the log file for more details.'],
			['messageSend' => 'test message', 'messageExpected' => 'test message'],
		];
	}

	/**
	 * @dataProvider dataTestGetSSODisplayName
	 *
	 * @param string $configuredDisplayName
	 * @param string $expected
	 */
	public function testGetSSODisplayName($configuredDisplayName, $expected) {
		$this->config->expects($this->any())->method('getAppValue')
			->with('user_saml', 'general-idp0_display_name')
			->willReturn($configuredDisplayName);

		$result = $this->invokePrivate($this->samlController, 'getSSODisplayName');

		$this->assertSame($expected, $result);
	}

	public function dataTestGetSSODisplayName() {
		return [
			['My identity provider', 'My identity provider'],
			['', 'SSO & SAML log in']
		];
	}
}
