<?php

namespace OCA\User_SAML;

use OCP\IConfig;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

class GroupDuplicateChecker
{
	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @var IGroupManager
	 */
	protected $groupManager;

	/**
	 * @var ILogger
	 */
	protected $logger;

	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		ILogger $logger
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	public function checkForDuplicates(string $group): void {
		$existingGroup = $this->groupManager->get($group);
		if ($existingGroup !== null) {
			$reflection = new \ReflectionClass($existingGroup);
			$property = $reflection->getProperty('backends');
			$property->setAccessible(true);
			$backends = $property->getValue($existingGroup);
			if ($backends) {
				foreach ($backends as $backend) {
					if ($backend instanceof GroupBackend) {
						return;
					}
				}
			}

			$this->logger->warning(
				'Group {name} already existing in other backend',
				[
					'name' => $group
				]
			);
		}
	}
}
