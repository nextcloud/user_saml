<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OC\Group\Database;
use OCA\User_SAML\Service\GroupMigration;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GroupMigrationManual extends Base implements LoggerInterface {
	private ?OutputInterface $output = null;

	public function __construct(
		private readonly GroupMigration $groupMigration,
		private readonly IGroupManager $groupManager,
	) {
		parent::__construct();
		$this->groupMigration->setLogger($this);
	}

	#[\Override]
	protected function configure(): void {
		$this->setName('saml:group-migration:force');
		$this->setDescription('Force migration of all groups from Database to SAML backend. Groups with non-saml members are skipped.');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->output = $output;
		try {
			$backend = $this->findBackend();
			$groupsToTreat = $this->findGroupIds($backend);
			$groupsToTreat = $this->groupMigration->getGroupsToMigrate($groupsToTreat, $groupsToTreat);
		} catch (\UnexpectedValueException) {
			$output->writeln('<error>Failed to find database group backend</error>');
			return self::FAILURE;
		}

		$failures = 0;
		foreach ($groupsToTreat as $group) {
			if (!$this->groupMigration->migrateGroup($group)) {
				$output->writeln('<error>Failed to migrate ' . $group . '</error>');
				$failures++;
			}
		}
		if ($failures === 0) {
			$output->writeln('<info>All groups were successfully migrated</info>');
			return self::SUCCESS;
		}

		return self::FAILURE;
	}

	private function findBackend(): Database {
		$groupBackends = $this->groupManager->getBackends();
		foreach ($groupBackends as $backend) {
			if ($backend instanceof Database) {
				return $backend;
			}
		}
		throw new \UnexpectedValueException();
	}

	private function findGroupIds(Database $backend): array {
		$groupIds = $backend->getGroups();
		$adminGroupIndex = array_search('admin', $groupIds, true);
		if ($adminGroupIndex !== false) {
			unset($groupIds[$adminGroupIndex]);
		}
		return array_values($groupIds);
	}

	/*
	 * Log functions to implement LoggerInterface so that information makes it to the cli output
	 */

	/**
	 * @param string $message
	 */
	#[\Override]
	public function emergency($message, array $context = []): void {
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function alert($message, array $context = []): void {
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function critical($message, array $context = []): void {
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function error($message, array $context = []): void {
		$this->log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function warning($message, array $context = []): void {
		$this->log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function notice($message, array $context = []): void {
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function info($message, array $context = []): void {
		$this->log(LogLevel::INFO, $message, $context);
	}

	/**
	 * @param string $message
	 */
	#[\Override]
	public function debug($message, array $context = []): void {
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 */
	#[\Override]
	public function log($level, $message, array $context = []): void {
		$tag = match($level) {
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR => 'error',
			LogLevel::WARNING,
			LogLevel::NOTICE => 'warning',
			LogLevel::INFO => 'info',
			default => '',
		};
		$flags = match($level) {
			LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
			default => 0,
		};
		$message = $this->interpolateMessage($message, $context);
		if ($tag !== '') {
			$message = '<' . $tag . '>' . $message . '<' . $tag . '/>';
		}
		$this->output?->writeln($message, $flags);
	}

	private function interpolateMessage(string $message, array $context): string {
		$replace = [];
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}
		return strtr($message, $replace);
	}
}
