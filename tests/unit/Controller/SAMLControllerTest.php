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

use Exception;
use OCA\User_SAML\Controller\SAMLController;
use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use OCP\Security\ICrypto;
use Test\TestCase;

class SAMLControllerTest extends TestCase {
	/** @var UserResolver|\PHPUnit\Framework\MockObject\MockObject */
	protected $userResolver;
	/** @var UserData|\PHPUnit\Framework\MockObject\MockObject */
	private $userData;
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
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;
	/** @var ICrypto|MockObject */
	private $crypto;
	/** @var SAMLController */
	private $samlController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->userBackend = $this->createMock(UserBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->l = $this->createMock(IL10N::class);
		$this->userResolver = $this->createMock(UserResolver::class);
		$this->userData = $this->createMock(UserData::class);
		$this->crypto = $this->createMock(ICrypto::class);

		$this->l->expects($this->any())->method('t')->willReturnCallback(
			function ($param) {
				return $param;
			}
		);

		$this->config->expects($this->any())->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$this->samlController = new SAMLController(
			'user_saml',
			$this->request,
			$this->session,
			$this->userSession,
			$this->samlSettings,
			$this->userBackend,
			$this->config,
			$this->urlGenerator,
			$this->logger,
			$this->l,
			$this->userResolver,
			$this->userData,
			$this->crypto
		);
	}

	public function testLoginWithInvalidAppValue() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_saml', 'type')
			->willReturn('UnknownValue');

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Type of "UnknownValue" is not supported for user_saml');

		$this->samlController->login(1);
	}

	public function samlUserDataProvider() {
		$userNotExisting = 0;
		$userExisting = 1;
		$userLazyExisting = 2;

		$apDisabled = 0;
		$apEnabled = 1;
		$apEnabledUnsuccessful = 2;

		return  [
			[ # 0 - Not existing uid in settings array
				[
					'foo' => 'bar',
					'bar' => 'foo',
				],
				'https://nextcloud.com/notProvisioned/',
				$userNotExisting,
				$apDisabled
			],
			[ # 1 - existing user
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => 'MyUid',
				],
				'https://nextcloud.com/absolute/',
				$userExisting,
				$apDisabled
			],
			[ # 2 - existing user and uid attribute in array
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => ['MyUid'],
				],
				'https://nextcloud.com/absolute/',
				$userExisting,
				$apDisabled
			],
			[ # 3 - Not existing user with provisioning
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => 'MyUid',
				],
				'https://nextcloud.com/absolute/',
				$userNotExisting,
				$apEnabled
			],
			[ # 4 - Not existing user with malfunctioning backend
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => 'MyUid',
				],
				'https://nextcloud.com/notProvisioned/',
				$userNotExisting,
				$apEnabledUnsuccessful
			],
			[ # 5 - Not existing user without provisioning
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => 'MyUid',
				],
				'https://nextcloud.com/notProvisioned/',
				$userNotExisting,
				$apDisabled
			],
			[ # 6 - Not yet mapped user without provisioning
				[
					'foo' => 'bar',
					'bar' => 'foo',
					'uid' => 'MyUid',
				],
				'https://nextcloud.com/absolute/',
				$userLazyExisting,
				$apDisabled
			],
		];
	}

	/**
	 * @dataProvider samlUserDataProvider
	 */
	public function testLoginWithEnvVariable(array $samlUserData, string $redirect, int $userState, int $autoProvision) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function (string $app, string $key) {
				if ($app === 'user_saml') {
					if ($key === 'type') {
						return 'environment-variable';
					}
					if ($key === 'general-uid_mapping') {
						return 'uid';
					}
				}
				return null;
			});

		$this->session
			->expects($this->once())
			->method('get')
			->with('user_saml.samlUserData')
			->willReturn($samlUserData);

		$this->userData
			->expects($this->once())
			->method('setAttributes')
			->with($samlUserData);
		$this->userData
			->expects($this->any())
			->method('getAttributes')
			->willReturn($samlUserData);
		$this->userData
			->expects($this->any())
			->method('hasUidMappingAttribute')
			->willReturn(isset($samlUserData['uid']));
		$this->userData
			->expects(isset($samlUserData['uid']) ? $this->any() : $this->never())
			->method('getOriginalUid')
			->willReturn('MyUid');
		$this->userData
			->expects($this->any())
			->method('testEncodedObjectGUID')
			->willReturnCallback(function ($uid) {
				return $uid;
			});
		$this->userData
			->expects($this->any())
			->method('getEffectiveUid')
			->willReturn($userState > 0 ? 'MyUid' : '');

		if (strpos($redirect, 'notProvisioned') !== false) {
			$this->urlGenerator
				->expects($this->once())
				->method('linkToRouteAbsolute')
				->with('user_saml.SAML.notProvisioned')
				->willReturn($redirect);
		} else {
			$this->urlGenerator
				->expects($this->once())
				->method('getAbsoluteURL')
				->willReturn($redirect);
		}

		$this->userResolver
			->expects($this->any())
			->method('userExists')
			->with('MyUid')
			->willReturn($userState === 1);

		if (isset($samlUserData['uid']) && !($userState === 0 && $autoProvision === 0)) {
			/** @var IUser|MockObject $user */
			$user = $this->createMock(IUser::class);
			$im = $this->userResolver
				->expects($this->once())
				->method('findExistingUser')
				->with('MyUid');
			if ($autoProvision < 2) {
				$im->willReturn($user);
			} else {
				$im->willThrowException(new NoUserFoundException());
			}

			$user
				->expects($this->exactly((int)($autoProvision < 2)))
				->method('updateLastLoginTimestamp');

			if ($userState === 0) {
				$this->userResolver
					->expects($this->any())
					->method('findExistingUserId')
					->with('MyUid', true)
					->willThrowException(new NoUserFoundException());
			} elseif ($userState === 2) {
				$this->userResolver
					->expects($this->any())
					->method('findExistingUserId')
					->with('MyUid', true)
					->willReturn('MyUid');
			}
		}
		$this->userBackend
			->expects($this->any())
			->method('getCurrentUserId')
			->willReturn(isset($samlUserData['uid']) ? 'MyUid' : '');
		$this->userBackend
			->expects($autoProvision > 0 ? $this->once() : $this->any())
			->method('autoprovisionAllowed')
			->willReturn($autoProvision > 0);
		$this->userBackend
			->expects($this->exactly(min(1, $autoProvision)))
			->method('createUserIfNotExists')
			->with('MyUid');


		$expected = new RedirectResponse($redirect);
		$result = $this->samlController->login(1);
		$this->assertEquals($expected, $result);
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
		$result = $this->invokePrivate($this->samlController, 'getSSODisplayName', [$configuredDisplayName]);

		$this->assertSame($expected, $result);
	}

	public function dataTestGetSSODisplayName() {
		return [
			['My identity provider', 'My identity provider'],
			['', 'SSO & SAML log in']
		];
	}
}
