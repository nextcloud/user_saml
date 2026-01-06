<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Jobs;

use OCA\User_SAML\Db\SessionDataMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class CleanSessionData extends TimedJob {
	protected const NC_AUTH_TOKEN_TABLE = 'authtoken';

	public function __construct(
		protected IDBConnection $dbc,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);

		$this->setInterval(86400);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		$this->setAllowParallelRuns(false);
	}

	protected function run(mixed $argument): void {
		$missingSessionIds = $this->findInvalidatedSessions();
		$this->deleteInvalidatedSessions($missingSessionIds);
	}

	protected function findInvalidatedSessions(): array {
		$qb = $this->dbc->getQueryBuilder();
		$qb->select('s.id')
			->from(SessionDataMapper::SESSION_DATA_TABLE_NAME, 's')
			->leftJoin('s', self::NC_AUTH_TOKEN_TABLE, 'a',
				$qb->expr()->eq('s.token_id', 'a.id'),
			)
			->where($qb->expr()->isNull('a.id'))
			->setMaxResults(1000);

		$invalidatedSessionsResult = $qb->executeQuery();
		$invalidatedSessionIds = $invalidatedSessionsResult->fetchAll(\PDO::FETCH_COLUMN);
		$invalidatedSessionsResult->closeCursor();

		return $invalidatedSessionIds;
	}

	protected function deleteInvalidatedSessions(array $invalidatedSessionIds): void {
		$qb = $this->dbc->getQueryBuilder();
		$qb->delete(SessionDataMapper::SESSION_DATA_TABLE_NAME)
			->where($qb->expr()->in(
				'id',
				$qb->createNamedParameter($invalidatedSessionIds, IQueryBuilder::PARAM_STR_ARRAY)
			));
		$qb->executeStatement();
	}
}
