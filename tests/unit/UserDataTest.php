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
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserDataTest extends TestCase {
	protected UserResolver&MockObject $resolver;
	protected SAMLSettings&MockObject $samlSettings;
	protected UserData $userData;

	#[Override]
	public function setUp(): void {
		parent::setUp();

		$this->resolver = $this->createMock(UserResolver::class);
		$this->samlSettings = $this->createMock(SAMLSettings::class);

		$this->userData = new UserData($this->resolver, $this->samlSettings);
	}

	public function objectGuidProvider(): array {
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

	#[DataProvider(methodName: 'objectGuidProvider')]
	public function testTestEncodedObjectGUID(string $input, string $expectation): void {
		$uid = $this->invokePrivate($this->userData, 'testEncodedObjectGUID', [$input]);
		$this->assertSame($expectation, $uid);
	}
}
