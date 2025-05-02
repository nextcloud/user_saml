<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$entity->setId($id);
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
		} catch (DoesNotExistException) {
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
			$newId = $entity->getId() + 1; // autoincrement manually
		} catch (DoesNotExistException) {
			$newId = 1;
		}

		$newEntity = new ConfigurationsEntity();
		$newEntity->setId($newId);
		$newEntity->importConfiguration([]);
		return $this->insert($newEntity)->getId();
	}
}
