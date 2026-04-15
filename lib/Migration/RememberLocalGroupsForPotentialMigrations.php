<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use OC\Group\Database;
use OCA\User_SAML\GroupManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;
use UnexpectedValueException;
use function json_encode;

class RememberLocalGroupsForPotentialMigrations implements IRepairStep {
	public function __construct(
		private readonly IGroupManager $groupManager,
		private readonly IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function getName(): string {
		return 'Remember local groups that might belong to SAML';
	}

	#[Override]
	public function run(IOutput $output): void {
		$candidateInfo = $this->appConfig->getAppValueString(GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
		if ($candidateInfo !== '') {
			return;
		}

		try {
			$backend = $this->findBackend();
			$groupIds = $this->findGroupIds($backend);
		} catch (UnexpectedValueException) {
			return;
		}

		$this->appConfig->setAppValueString(
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			json_encode([
				'dropAfter' => time() + 86400 * 60, // 60 days
				'groups' => $groupIds
			], JSON_THROW_ON_ERROR)
		);
	}

	protected function findGroupIds(Database $backend): array {
		$groupIds = $backend->getGroups();
		$adminGroupIndex = array_search('admin', $groupIds, true);
		if ($adminGroupIndex !== false) {
			unset($groupIds[$adminGroupIndex]);
		}
		return $groupIds;
	}

	protected function findBackend(): Database {
		$groupBackends = $this->groupManager->getBackends();
		foreach ($groupBackends as $backend) {
			if ($backend instanceof Database) {
				return $backend;
			}
		}
		throw new UnexpectedValueException();
	}
}
