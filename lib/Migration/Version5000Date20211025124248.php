<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version5000Date20211025124248 extends SimpleMigrationStep {
	private const IDP_CONFIG_KEYS = [
		'general-idp0_display_name',
		'general-uid_mapping',
		'idp-entityId',
		'idp-singleLogoutService.responseUrl',
		'idp-singleLogoutService.url',
		'idp-singleSignOnService.url',
		'idp-x509cert',
		'security-authnRequestsSigned',
		'security-general',
		'security-logoutRequestSigned',
		'security-logoutResponseSigned',
		'security-lowercaseUrlencoding',
		'security-nameIdEncrypted',
		'security-offer',
		'security-required',
		'security-signatureAlgorithm',
		'security-signMetadata',
		'security-sloWebServerDecode',
		'security-wantAssertionsEncrypted',
		'security-wantAssertionsSigned',
		'security-wantMessagesSigned',
		'security-wantNameId',
		'security-wantNameIdEncrypted',
		'security-wantXMLValidation',
		'saml-attribute-mapping-displayName_mapping',
		'saml-attribute-mapping-email_mapping',
		'saml-attribute-mapping-group_mapping',
		'saml-attribute-mapping-home_mapping',
		'saml-attribute-mapping-quota_mapping',
		'sp-x509cert',
		'sp-name-id-format',
		'sp-privateKey',
	];

	/** @var IDBConnection */
	private $dbc;

	/** @var ?IQueryBuilder */
	private $insertQuery;

	/** @var ?IQueryBuilder */
	private $deleteQuery;

	/** @var ?IQueryBuilder */
	private $readQuery;

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
	}

	/**
	 * @param IOutput $output
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('user_saml_configurations')) {
			$table = $schema->createTable('user_saml_configurations');
			$table->addColumn('id', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->addColumn('name', Types::STRING, [
				'length' => 256,
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('configuration', Types::TEXT, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id'], 'idx_user_saml_config');
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure():IschemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$prefixes = $this->fetchPrefixes();
		foreach ($prefixes as $prefix) {
			$keyStart = $prefix === 1 ? '' : $prefix . '-';
			$configKeys = array_reduce(
				self::IDP_CONFIG_KEYS,
				function (array $carry, string $rawConfigKey) use ($keyStart): array {
					$carry[] = $keyStart . $rawConfigKey;
					return $carry;
				},
				[]
			);

			$configData = $keysToDelete = [];
			$gRows = $this->readConfiguration($configKeys);
			while ($row = $gRows->current()) {
				$configData[$this->normalizeKey($row['configkey'])] = $row['configvalue'];
				$keysToDelete[] = $row['configkey'];
				$gRows->next();
			}

			if (empty($configData)) {
				continue; // No config found
			}

			if ($this->insertConfiguration($prefix, $configData) && !empty($keysToDelete)) {
				$this->deleteOldConfiguration($keysToDelete);
			}
		}

		$this->deletePrefixes();
	}

	/**
	 * @psalm-param list<string> $keys the list of keys to delete
	 */
	protected function deleteOldConfiguration(array $keys): bool {
		if (!$this->deleteQuery) {
			$this->deleteQuery = $this->dbc->getQueryBuilder();

			$this->deleteQuery->delete('appconfig')
				->where($this->deleteQuery->expr()->eq('appid', $this->deleteQuery->createNamedParameter('user_saml')))
				->andWhere($this->deleteQuery->expr()->in('configkey', $this->deleteQuery->createParameter('cfgKeys')));
		}

		$deletedRows = $this->deleteQuery
			->setParameter('cfgKeys', $keys, IQueryBuilder::PARAM_STR_ARRAY)
			->executeStatement();

		return $deletedRows > 0;
	}

	/**
	 * @param array<string, string> $configData The key-value map of config to save
	 */
	protected function insertConfiguration(int $id, array $configData): bool {
		if (!$this->insertQuery) {
			$this->insertQuery = $this->dbc->getQueryBuilder();
			$this->insertQuery->insert('user_saml_configurations')
				->values([
					'id' => $this->insertQuery->createParameter('configId'),
					'name' => $this->insertQuery->createParameter('displayName'),
					'configuration' => $this->insertQuery->createParameter('configuration'),
				]);
		}

		$insertedRows = $this->insertQuery
			->setParameter('configId', $id)
			->setParameter('displayName', $configData['general-idp0_display_name'] ?? '')
			->setParameter('configuration', \json_encode($configData, JSON_THROW_ON_ERROR))
			->executeStatement();

		return $insertedRows > 0;
	}

	/** @psalm-param list<string> $configKeys */
	protected function readConfiguration(array $configKeys): \Generator {
		if (!$this->readQuery) {
			$this->readQuery = $this->dbc->getQueryBuilder();
			$this->readQuery->select('configkey', 'configvalue')
				->from('appconfig')
				->where($this->readQuery->expr()->eq('appid', $this->readQuery->createNamedParameter('user_saml')))
				->andWhere($this->readQuery->expr()->in('configkey', $this->readQuery->createParameter('cfgKeys')));
		}

		$r = $this->readQuery->setParameter('cfgKeys', $configKeys, IQueryBuilder::PARAM_STR_ARRAY)
			->executeQuery();

		while ($row = $r->fetch()) {
			yield $row;
		}
		$r->closeCursor();
	}

	protected function normalizeKey(string $prefixedKey): string {
		$isPrefixed = \preg_match('/^[0-9]*-/', $prefixedKey, $matches);
		if ($isPrefixed === 0) {
			return $prefixedKey;
		} elseif ($isPrefixed === 1) {
			return \substr($prefixedKey, strlen($matches[0]));
		}
		throw new \RuntimeException('Invalid regex pattern');
	}

	/** @psalm-return list<int> */
	protected function fetchPrefixes(): array {
		$q = $this->dbc->getQueryBuilder();
		$q->select('configvalue')
			->from('appconfig')
			->where($q->expr()->eq('appid', $q->createNamedParameter('user_saml')))
			->andWhere($q->expr()->eq('configkey', $q->createNamedParameter('providerIds')));

		$r = $q->executeQuery();
		$prefixes = $r->fetchOne();
		if ($prefixes === false) {
			return [1]; // 1 is the default value for providerIds
		}
		return array_map('intval', explode(',', (string)$prefixes));
	}

	protected function deletePrefixes(): void {
		$q = $this->dbc->getQueryBuilder();
		$q->delete('appconfig')
			->where($q->expr()->eq('appid', $q->createNamedParameter('user_saml')))
			->andWhere($q->expr()->eq('configkey', $q->createNamedParameter('providerIds')))
			->executeStatement();
	}
}
