<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace {
	if (!function_exists('p')) {
		function p($string): void {
			print(htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8'));
		}
	}
}

namespace OCA\User_SAML\Tests\Template {
	use OCP\IL10N;
	use OCP\Util;
	use Test\TestCase;

	class LoginPostTemplateTest extends TestCase {
		public function testLoginPostTemplateEscapesValues(): void {
			$l = $this->createMock(IL10N::class);
			$l->method('t')->willReturnCallback(static fn (string $text): string => $text);

			$_ = [
				'ssoUrl' => 'https://example.com/" onmouseover="alert(1)',
				'samlRequest' => '"><input name="submit">',
				'relayState' => '"><img src=x onerror=alert(1)>',
				'sigAlg' => '"><svg onload=alert(1)>',
				'signature' => '"><div>sig</div>',
				'nonce' => '" onload="alert(1)',
			];

			ob_start();
			include __DIR__ . '/../../../templates/login_post.php';
			$output = ob_get_clean();

			$this->assertNotFalse($output);
			$this->assertStringContainsString(
				'action="' . Util::sanitizeHTML($_['ssoUrl']) . '"',
				$output
			);
			$this->assertStringContainsString(
				'name="SAMLRequest" value="' . Util::sanitizeHTML($_['samlRequest']) . '"',
				$output
			);
			$this->assertStringContainsString(
				'name="RelayState" value="' . Util::sanitizeHTML($_['relayState']) . '"',
				$output
			);
			$this->assertStringContainsString(
				'name="SigAlg" value="' . Util::sanitizeHTML($_['sigAlg']) . '"',
				$output
			);
			$this->assertStringContainsString(
				'name="Signature" value="' . Util::sanitizeHTML($_['signature']) . '"',
				$output
			);
			$this->assertStringContainsString(
				'nonce="' . Util::sanitizeHTML($_['nonce']) . '"',
				$output
			);
		}
	}
}
