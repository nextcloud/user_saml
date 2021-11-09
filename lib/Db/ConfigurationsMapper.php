<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ConfigurationsMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'user_saml_configurations', ConfigurationsEntity::class);
	}

	public function set(int $id, array $configuration): void {
		$entity = new ConfigurationsEntity();
		$entity->setId($id);
		$entity->importConfiguration($configuration);
		$this->insertOrUpdate($entity);
	}

	public function deleteById(int $id): void {
		$entity = new ConfigurationsEntity();
		$entity->setId($id);;
		$this->delete($entity);
	}

	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'configuration')
			->from('user_saml_configurations')
			->orderBy('id', 'ASC');

		/** @var ConfigurationsEntity $entity */
		$entities = $this->findEntities($qb);
		$result = [];
		foreach ($entities as $entity) {
			$result[$entity->getId()] = $entity->getConfigurationArray();
		}
		return $result;
	}

	public function get(int $idp): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'configuration')
			->from('user_saml_configurations')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($idp, IQueryBuilder::PARAM_INT)));

		/** @var ConfigurationsEntity $entity */
		try {
			$entity = $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return [];
		}
		return $entity->getConfigurationArray();
	}

	public function reserveId(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from('user_saml_configurations')
			->orderBy('id', 'DESC')
			->setMaxResults(1);

		try {
			$entity = $this->findEntity($qb);
			$newId = $entity->getId() + 1;
		} catch (DoesNotExistException $e) {
			$newId = 1;
		}

		$newEntity = new ConfigurationsEntity();
		$newEntity->setId($newId);
		$newEntity->importConfiguration([]);
		return $this->insert($newEntity)->getId();
	}

}
