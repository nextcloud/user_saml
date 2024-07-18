<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\Service\GroupMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GroupMigrationCopyIncomplete extends Base {
	public function __construct(
		protected GroupMigration $groupMigration,
		protected LoggerInterface $logger,
	) {
		parent::__construct();
	}
	protected function configure(): void {
		$this->setName('saml:group-migration:copy-incomplete-members');
		$this->setDescription('Transfers remaining group members from old local to current SAML groups');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$groupsToTreat = $this->groupMigration->findGroupsWithLocalMembers();
		if (empty($groupsToTreat)) {
			if ($output->isVerbose()) {
				$output->writeln('<info>No pending group member transfer</info>');
			}
			return 0;
		}

		if (!$this->doMemberTransfer($groupsToTreat, $output)) {
			if (!$output->isQuiet()) {
				$output->writeln('<comment>Not all group members could be transferred completely. Rerun this command or check the Nextcloud log.</comment>');
			}
			return 1;
		}

		if (!$output->isQuiet()) {
			$output->writeln('<info>All group members could be transferred completely.</info>');
		}
		return 0;
	}

	/**
	 * @param string[]|array<empty> $groups
	 * @param OutputInterface $output
	 * @return bool
	 */
	protected function doMemberTransfer(array $groups, OutputInterface $output): bool {
		$errorOccurred = false;
		for ($i = 0; $i < 2; $i++) {
			$retry = [];
			foreach ($groups as $gid) {
				try {
					$isComplete = $this->groupMigration->migrateGroupUsers($gid);
					if (!$isComplete) {
						$retry[] = $gid;
					} else {
						$this->groupMigration->cleanUpOldGroupUsers($gid);
						if ($output->isVerbose()) {
							$output->writeln(sprintf('<info>Members transferred successfully for group %s</info>', $gid));
						}
					}
				} catch (Throwable $e) {
					$errorOccurred = true;
					if (!$output->isQuiet()) {
						$output->writeln(sprintf('<error>Failed to transfer users from group %s: %s</error>', $gid, $e->getMessage()));
					}
					$this->logger->warning('Error while transferring group members of {gid}', ['gid' => $gid, 'exception' => $e]);
				}
			}
			if (empty($retry)) {
				return true;
			}
			/** @var string[]|array<empty> $groups */
			$groups = $retry;
		}
		if (!empty($groups) && !$output->isQuiet()) {
			$output->writeln(sprintf(
				'<comment>Members not or incompletely transferred for groups: %s</comment>',
				implode(', ', $groups)
			));
		}
		return empty($groups) && !$errorOccurred;
	}
}
