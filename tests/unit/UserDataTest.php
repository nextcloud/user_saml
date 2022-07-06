<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
