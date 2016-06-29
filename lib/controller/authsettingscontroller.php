<?php
/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_SAML\Controller;

use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDb;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

class AuthSettingsController extends Controller {
	/** @var IUserManager */
	private $userManager;
	/** @var ISession */
	private $session;
	/** @var string */
	private $uid;
	/** @var ISecureRandom */
	private $random;
	/** @var IDb */
	private $db;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param ISession $session
	 * @param ISecureRandom $random
	 * @param IDb $db
	 * @param string $uid
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								ISession $session,
								ISecureRandom $random,
								IDb $db,
								$uid) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->uid = $uid;
		$this->session = $session;
		$this->random = $random;
		$this->db = $db;
	}
	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}

		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'uid', 'name', 'token')
			->from('user_saml_auth_token')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->setMaxResults(1000);
		$result = $qb->execute();
		$data = $result->fetchAll();
		$result->closeCursor();

		foreach($data as $key => $entry) {
			unset($data[$key]['token']);
			unset($data[$key]['uid']);
			$data[$key]['id'] = (int)$data[$key]['id'];
			$data[$key]['type'] = 1;
		}

		return $data;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $name
	 * @return JSONResponse
	 */
	public function create($name) {
		$token = $this->generateRandomDeviceToken();

		$values = [
			'uid' => $this->uid,
			'name' => $name,
			'token' => password_hash($token, PASSWORD_DEFAULT),
		];

		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->insert('user_saml_auth_token');
		foreach($values as $column => $value) {
			$qb->setValue($column, $qb->createNamedParameter($value));
		}
		$qb->execute();

		return [
			'token' => $token,
			'loginName' => $name,
			'deviceToken' => [
				'id' => $qb->getLastInsertId(),
				'name' => $name,
				'type' => 1,
 			],
		];
	}
	/**
	 * Return a 20 digit device password
	 *
	 * Example: ABCDE-FGHIJ-KLMNO-PQRST
	 *
	 * @return string
	 */
	private function generateRandomDeviceToken() {
		$groups = [];
		for ($i = 0; $i < 4; $i++) {
			$groups[] = $this->random->generate(5, implode('', range('A', 'Z')));
		}
		return implode('-', $groups);
	}
	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return JSONResponse
	 */
	public function destroy($id) {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}

		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete('user_saml_auth_token')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())));
		$qb->execute();

		return [];
	}
}