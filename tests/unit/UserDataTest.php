<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use OCP\IConfig;
use Test\TestCase;

class UserDataTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var UserResolver|\PHPUnit\Framework\MockObject\MockObject */
	protected $resolver;
	/** @var SAMLSettings|\PHPUnit\Framework\MockObject\MockObject */
	protected $samlSettings;
	/** @var UserData */
	protected $userData;

	public function setUp(): void {
		parent::setUp();

		$this->resolver = $this->createMock(UserResolver::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);
		$this->config = $this->createMock(IConfig::class);

		$this->userData = new UserData($this->resolver, $this->samlSettings, $this->config);
	}

	public function objectGuidProvider() {
		return [
			['Joey No Conversion', 'Joey No Conversion'],
			['no@convers.ion', 'no@convers.ion'],
			['a0aa9ed8-6b48-1034-8ad7-8fb78330d80a', 'a0aa9ed8-6b48-1034-8ad7-8fb78330d80a'],
			['EDE70D16-B9D5-4E9A-ABD7-614D17246E3F', 'EDE70D16-B9D5-4E9A-ABD7-614D17246E3F'],
			['Tm8gY29udmVyc2lvbgo=', 'Tm8gY29udmVyc2lvbgo='],
			['ASfjU2OYEd69ZgAVF4pePA==', '53E32701-9863-DE11-BD66-0015178A5E3C'],
			['aaabbbcc@aa.bbbccdd.eee.ff', 'aaabbbcc@aa.bbbccdd.eee.ff'],
			['aaabbbcccaa.bbbccdddeee', 'aaabbbcccaa.bbbccdddeee']
		];
	}

	/**
	 * @dataProvider objectGuidProvider
	 */
	public function testTestEncodedObjectGUID(string $input, string $expectation) {
		$uid = $this->invokePrivate($this->userData, 'testEncodedObjectGUID', [$input]);
		$this->assertSame($expectation, $uid);
	}
}
