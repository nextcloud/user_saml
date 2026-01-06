<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/** @template-extends QBMapper<SessionData> */
class SessionDataMapper extends QBMapper {
	public const SESSION_DATA_TABLE_NAME = 'user_saml_session_data';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::SESSION_DATA_TABLE_NAME, SessionData::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function retrieve(string $sessionId): SessionData {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::SESSION_DATA_TABLE_NAME)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($sessionId)));
		return $this->findEntity($qb);
	}
}
