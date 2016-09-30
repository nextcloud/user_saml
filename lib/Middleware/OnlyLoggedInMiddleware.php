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

namespace OCA\User_SAML\Middleware;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
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

	/**
	 * @param IControllerMethodReflector $reflector
	 * @param IUserSession $userSession
	 */
	public function __construct(IControllerMethodReflector $reflector,
								IUserSession $userSession) {
		$this->reflector = $reflector;
		$this->userSession = $userSession;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName){
		if($this->reflector->hasAnnotation('OnlyUnauthenticatedUsers') && $this->userSession->isLoggedIn()) {
			throw new \Exception('User is already logged-in');
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if($exception->getMessage() === 'User is already logged-in') {
			return new JSONResponse('User is already logged-in', 403);
		}

		throw $exception;
	}
}
