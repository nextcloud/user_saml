<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Middleware;

use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IURLGenerator;
use OCP\IUserSession;

/**
 * Class OnlyLoggedInMiddleware prevents access to a controller method if the user
 * is already logged-in.
 *
 * @package OCA\User_SAML\MiddleWare
 */
class OnlyLoggedInMiddleware extends Middleware {
	/** @var IControllerMethodReflector */
	private $reflector;
	/** @var IUserSession */
	private $userSession;
	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(
		IControllerMethodReflector $reflector,
		IUserSession $userSession,
		IURLGenerator $urlGenerator
	) {
		$this->reflector = $reflector;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotation('OnlyUnauthenticatedUsers') && $this->userSession->isLoggedIn()) {
			throw new \Exception('User is already logged-in');
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return RedirectResponse
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception->getMessage() === 'User is already logged-in') {
			return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/'));
		}

		throw $exception;
	}
}
