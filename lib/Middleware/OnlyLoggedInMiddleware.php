<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Middleware;

use OCA\User_SAML\Attributes\OnlyUnauthenticatedUsers;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Override;

/**
 * Class OnlyLoggedInMiddleware prevents access to a controller method if the user
 * is already logged-in.
 *
 * @package OCA\User_SAML\MiddleWare
 */
class OnlyLoggedInMiddleware extends Middleware {

	public function __construct(
		private readonly IUserSession $userSession,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	#[Override]
	public function beforeController($controller, $methodName): void {
		if ($this->hasAttribute($controller, $methodName) && $this->userSession->isLoggedIn()) {
			throw new \Exception('User is already logged-in');
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 */
	#[Override]
	public function afterException($controller, $methodName, \Exception $exception): RedirectResponse {
		if ($exception->getMessage() === 'User is already logged-in') {
			return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
		}

		throw $exception;
	}

	protected function hasAttribute(object $controller, string $methodName): bool {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		return !empty($reflectionMethod->getAttributes(OnlyUnauthenticatedUsers::class));
	}
}
