<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests\Controller;

use Exception;
use OCA\User_SAML\Controller\SAMLController;
use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCA\User_SAML\Exceptions\UserFilterViolationException;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Security\ITrustedDomainHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
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
	/** @var SAMLSettings|\PHPUnit_Framework_MockObject_MockObject */
	private $samlSettings;
	/** @var UserBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;
	/** @var ICrypto|MockObject */
	private $crypto;
	/** @var SAMLController */
	private $samlController;
	private ITrustedDomainHelper|MockObject $trustedDomainController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->userBackend = $this->createMock(UserBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l = $this->createMock(IL10N::class);
		$this->userResolver = $this->createMock(UserResolver::class);
		$this->userData = $this->createMock(UserData::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->trustedDomainController = $this->createMock(ITrustedDomainHelper::class);

		$this->l->expects($this->any())->method('t')->willReturnCallback(
			fn ($param) => $param
		);

		$this->config->expects($this->any())->method('getSystemValue')
			->willReturnCallback(fn ($key, $default) => $default);

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
			$this->crypto,
			$this->trustedDomainController
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
			->expects($this->any())
			->method('get')
			->willReturnCallback(fn (string $key) => match ($key) {
				'user_saml.samlUserData' => $samlUserData,
				'user_saml.Idp' => 1,
				default => null,
			});

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
			->willReturnCallback(fn ($uid) => $uid);
		$this->userData
			->expects($this->any())
			->method('getEffectiveUid')
			->willReturn($userState > 0 ? 'MyUid' : '');

		if (str_contains($redirect, 'notProvisioned')) {
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
			->expects($this->any())
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

	public function testNotPermitted() {
		$expected = new TemplateResponse('user_saml', 'notPermitted', [], 'guest');
		$this->assertEquals($expected, $this->samlController->notPermitted());
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

	public function userFilterDataProvider(): array {
		return [
			[ // 0 - test rejection by membership
				'Group C',
				'Group A, Group B',
				true,
				'User is member of a rejection group.',
			],
			[ // 1 - test rejection by required membership
				'Group D',
				'Group B',
				true,
				'User is not member of a required group.',
			],
			[ // 2 - test satisfy all requirements
				'Group D',
				'Group A',
				false,
			],
			[ // 3 - test no filtering
				null,
				null,
				false,
			],
		];
	}

	/**
	 * @dataProvider userFilterDataProvider
	 */
	public function testUserFilter(?string $rejectGroups, ?string $requireGroups, bool $isException, string $message = ''): void {
		$this->userData->expects($this->any())
			->method('getGroups')
			->willReturn(['Group A', 'Group C']);

		$this->session->expects($this->any())
			->method('get')
			->with('user_saml.Idp')
			->willReturn(1);

		$settings = [];
		if ($rejectGroups !== null && $requireGroups !== null) {
			$settings = [
				'saml-user-filter-reject_groups' => $rejectGroups,
				'saml-user-filter-require_groups' => $requireGroups,
			];
		}
		$this->samlSettings->expects($this->any())
			->method('get')
			->with(1)
			->willReturn($settings);

		$this->userBackend->expects($this->any())
			->method('autoprovisionAllowed')
			->willReturn(true);

		if ($isException) {
			$this->expectException(UserFilterViolationException::class);
			$this->expectExceptionMessage($message);
		} else {
			// Nothing to assert other than no exception being thrown
			$this->assertTrue(true);
		}

		$this->invokePrivate($this->samlController, 'assertGroupMemberships');
	}

	public function testUserFilterNotApplicable(): void {
		$this->userData->expects($this->never())
			->method('getGroups');

		$this->session->expects($this->never())
			->method('get');

		$this->userBackend->expects($this->any())
			->method('autoprovisionAllowed')
			->willReturn(false);

		$this->invokePrivate($this->samlController, 'assertGroupMemberships');
	}
}
