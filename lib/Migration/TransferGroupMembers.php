<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Migration;

use OCA\User_SAML\Service\GroupMigration;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Throwable;

class TransferGroupMembers implements IRepairStep {

	public function __construct(
		private readonly GroupMigration $groupMigration,
		private readonly LoggerInterface $logger,
	) {
	}

	public function getName(): string {
		return 'Move potential left members from old local groups to SAML groups';
	}

	public function run(IOutput $output): void {
		$groupsToTreat = $this->groupMigration->findGroupsWithLocalMembers();
		if (empty($groupsToTreat)) {
			return;
		}
		$hasError = false;
		$output->startProgress(count($groupsToTreat));
		foreach ($groupsToTreat as $gid) {
			try {
				if ($this->groupMigration->migrateGroupUsers($gid)) {
					$this->groupMigration->cleanUpOldGroupUsers($gid);
				}
			} catch (Throwable $e) {
				$hasError = true;
				$this->logger->warning('Error while transferring group members of {gid}', ['gid' => $gid, 'exception' => $e]);
			} finally {
				$output->advance();
			}
		}
		$output->finishProgress();
		if ($hasError) {
			$output->warning('There were errors while transferring group members to SAML groups. You may try later `occ saml:group-migration:copy-incomplete-members` later and check your nextcloud.log.');
		}
	}
}
