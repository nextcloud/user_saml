<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Command;

use OC\Core\Command\Base;
use OCA\User_SAML\UserBackend;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserAdd extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected UserBackend $backend,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}
	protected function configure(): void {
		$this
			->setName('saml:user:add')
			->setDescription('Add a SAML account')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'Account ID as provided by the IdP (must only contain a-z, A-Z, 0-9, -, _ and @)'
			)
			->addOption(
				'display-name',
				null,
				InputOption::VALUE_REQUIRED,
				'Name as presented in the web interface (can contain any characters)'
			)
			->addOption(
				'email',
				null,
				InputOption::VALUE_OPTIONAL,
				'Set user default email in user profile'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');

		if ($this->userManager->userExists($uid)) {
			$output->writeln('<error>The account "' . $uid . '" already exists.</error>');
			return 1;
		}

		if (!$output->isQuiet()) {
			$output->writeln('<info>The account "' . $uid . '" is to be added to the SAML backend.</info>');
		}

		try {
			$this->backend->createUserIfNotExists($uid);
		} catch (\Exception $e) {
			$output->writeln('<error>SAML create user ' . $e->getMessage() . '</error>');
			return 1;
		}

		try {
			$this->backend->setDisplayName($uid, $input->getOption('display-name'));
			$email = $input->getOption('email');
			if (!empty($email)) {
				$user = $this->userManager->get($uid);
				$user->setSystemEMailAddress($email);
			}
		} catch (\Exception $e) {
			$output->writeln('<error>SAML create user Email and DisplayName ' . $e->getMessage() . '</error>');
			return 1;
		}

		if (!$output->isQuiet()) {
			$output->writeln('<info>SAML user "' . $uid . '" added.</info>');
		}

		return 0;
	}

}
