<?php

declare(strict_types=1);

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
	];

	/** @var IDBConnection */
	private $dbc;

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
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
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
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

			if ($this->insertConfiguration($prefix, $configData) && !empty($keysToDelete)) {
				$this->deleteOldConfiguration($keysToDelete);
			}
		}

		$this->deletePrefixes();
	}

	protected function deleteOldConfiguration(array $keys): bool {
		static $deleteQuery;
		if (!$deleteQuery) {
			$deleteQuery = $this->dbc->getQueryBuilder();

			$deleteQuery->delete('appconfig')
				->where($deleteQuery->expr()->eq('appid', $deleteQuery->createNamedParameter('user_saml')))
				->andWhere($deleteQuery->expr()->in('configkey', $deleteQuery->createParameter('cfgKeys')));
		}

		$deletedRows = $deleteQuery
			->setParameter('cfgKeys', $keys, IQueryBuilder::PARAM_STR_ARRAY)
			->executeStatement();

		return $deletedRows > 0;
	}

	protected function insertConfiguration(int $id, array $configData): bool {
		static $insertQuery;
		if (!$insertQuery) {
			$insertQuery = $this->dbc->getQueryBuilder();
			$insertQuery->insert('user_saml_configurations')
				->values([
					'id' => $insertQuery->createParameter('configId'),
					'name' => $insertQuery->createParameter('displayName'),
					'configuration' => $insertQuery->createParameter('configuration'),
				]);
		}

		$insertedRows = $insertQuery
			->setParameter('configId', $id)
			->setParameter('displayName', $configData['general-idp0_display_name'] ?? '')
			->setParameter('configuration', \json_encode($configData, JSON_THROW_ON_ERROR))
			->executeStatement();

		return $insertedRows > 0;
	}

	protected function readConfiguration(array $configKeys): \Generator {
		static $readQuery;
		if (!$readQuery) {
			$readQuery = $this->dbc->getQueryBuilder();
		}
		$readQuery->select('configkey', 'configvalue')
			->from('appconfig')
			->where($readQuery->expr()->eq('appid', $readQuery->createNamedParameter('user_saml')))
			->andWhere($readQuery->expr()->in('configkey', $readQuery->createParameter('cfgKeys')));

		$r = $readQuery->setParameter('cfgKeys', $configKeys, IQueryBuilder::PARAM_STR_ARRAY)
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

	protected function fetchPrefixes(): array {
		$q = $this->dbc->getQueryBuilder();
		$q->select('configvalue')
			->from('appconfig')
			->where($q->expr()->eq('appid', $q->createNamedParameter('user_saml')))
			->andWhere($q->expr()->eq('configkey', $q->createNamedParameter('providerIds')));

		$r = $q->executeQuery();
		$prefixes = $r->fetchOne();
		if ($prefixes === false) {
			return [];
		}
		return array_map('intval', explode(',', $prefixes));
	}

	protected function deletePrefixes(): void {
		$q = $this->dbc->getQueryBuilder();
		$q->delete('appconfig')
			->where($q->expr()->eq('appid', $q->createNamedParameter('user_saml')))
			->andWhere($q->expr()->eq('configkey', $q->createNamedParameter('providerIds')))
			->executeStatement();
	}
}
