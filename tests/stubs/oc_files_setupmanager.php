<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files;

use OC\Files\Cache\FileAccess;
use OC\Files\Config\MountProviderCollection;
use OC\Share20\ShareDisableChecker;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use Override;
use Psr\Log\LoggerInterface;

class SetupManager {
	private const SETUP_WITH_CHILDREN = 1;
	private const SETUP_WITHOUT_CHILDREN = 0;

	public function __construct(
		private IEventLogger $eventLogger,
		private MountProviderCollection $mountProviderCollection,
		private IMountManager $mountManager,
		private IUserManager $userManager,
		private IEventDispatcher $eventDispatcher,
		private IUserMountCache $userMountCache,
		private ILockdownManager $lockdownManager,
		private IUserSession $userSession,
		ICacheFactory $cacheFactory,
		private LoggerInterface $logger,
		private IConfig $config,
		private ShareDisableChecker $shareDisableChecker,
		private IAppManager $appManager,
		private FileAccess $fileAccess,
		private IAppConfig $appConfig,
	) {
	}

	public function isSetupComplete(IUser $user): bool {
	}

	public function setupForUser(IUser $user): void {
	}

	/**
	 * Set up the root filesystem
	 */
	public function setupRoot(): void {
	}

	public function setupForPath(string $path, bool $includeChildren = false): void {
	}

	/**
	 * @param string $path
	 * @param string[] $providers
	 */
	public function setupForProvider(string $path, array $providers): void {
	}

	public function tearDown(): void {
	}

	/**
	 * Drops partially set-up mounts for the given user
	 *
	 * @param class-string<IMountProvider>[] $providers
	 */
	public function dropPartialMountsForUser(IUser $user, array $providers = []): void {
	}
}
