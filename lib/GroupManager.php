<?php

namespace OCA\User_SAML;

use OC\BackgroundJob\JobList;
use OC\Hooks\PublicEmitter;
use OCA\User_SAML\Jobs\MigrateGroups;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class GroupManager
{
	const LOCAL_GROUPS_CHECK_FOR_MIGRATION = 'localGroupsCheckForMigration';

	/**
	 * @var IDBConnection $db
	 */
	protected $db;

	/**
	 * @var GroupDuplicateChecker
	 */
	protected $duplicateChecker;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserManager */
	private $userManager;
	/** @var GroupBackend */
	private $ownGroupBackend;
	/** @var IConfig */
	private $config;
	/** @var JobList */
	private $jobList;


	public function __construct(
		IDBConnection $db,
		GroupDuplicateChecker $duplicateChecker,
		IGroupManager $groupManager,
		IUserManager $userManager,
		GroupBackend $ownGroupBackend,
		IConfig $config,
		JobList $jobList
	) {
		$this->db = $db;
		$this->duplicateChecker = $duplicateChecker;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->ownGroupBackend = $ownGroupBackend;
		$this->config = $config;
		$this->jobList = $jobList;
	}

	public function replaceGroups($uid, $samlGroups) {
		$user = $this->userManager->get($uid);
		if($user === null) {
			return;
		}
		$this->translateGroupToIds($samlGroups);
		$assigned = $this->groupManager->getUserGroups($uid);
		$this->removeGroups($user, array_diff($assigned, $samlGroups));
		$this->addGroups($uid, array_diff($samlGroups, $assigned));
	}

	protected function translateGroupToIds(array &$samlGroups) {
		array_walk($samlGroups, function (&$gid){
			$altGid = $this->ownGroupBackend->groupExistsWithDifferentGid($gid);
			if($altGid !== null) {
				$gid = $altGid;
			}
		});
	}

	public function removeGroups(IUser $user, array $groupIds) {
		foreach ($groupIds as $gid) {
			$this->removeGroup($user, $gid);
		}
	}

	public function removeGroup(IUser $user, string $gid) {
		$group = $this->groupManager->get($gid);
		if($group === null) {
			return;
		}
		$group->removeUser($user);
	}

	public function addGroups(IUser $user, $groupIds) {
		foreach ($groupIds as $gid) {
			$this->addGroup($user, $gid);
		}
	}

	public function addGroup(IUser $user, $gid) {
		try {
			$group = $this->findGroup($gid);
		} catch (\RuntimeException $e) {
			if($e->getCode() === 1) {
				$group = $this->createGroupInBackend($gid);
			} else if($e->getCode() === 2) {
				//FIXME: probably need config flag. Previous to 17, gid was used as displayname
				$group = $this->createGroupInBackend('__saml__' . $gid, $gid);
			} else {
				throw $e;
			}
		}

		$group->addUser($user);
	}

	protected function createGroupInBackend($gid, $originalGid = null) {
		if($this->groupManager instanceof PublicEmitter) {
			$this->groupManager->emit('\OC\Group', 'preCreate', array($gid));
		}
		if(!$this->ownGroupBackend->createGroup($gid, $originalGid ?? $gid)) {
			return;
		}

		$group = $this->groupManager->get($gid);
		if($this->groupManager instanceof PublicEmitter) {
			$this->groupManager->emit('\OC\Group', 'postCreate', array($group));
		}

		return $group;
	}

	protected function findGroup($gid): IGroup {
		$migrationWhiteList = $this->config->getAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			null
		);
		$strictBackendCheck = null === $migrationWhiteList;
		if(!$strictBackendCheck && in_array($gid, $migrationWhiteList['groups'], true)) {
			$group = $this->groupManager->get($gid);
			if($group === null) {
				//FIXME: specific Exception and/or constant error code
				throw new \RuntimeException('Group not found', 1);
			}
			return $group;
		}
		$group = $this->groupManager->get($gid);
		if($group === null) {
			//FIXME: specific Exception and/or constant error code
			throw new \RuntimeException('Group not found', 1);
		}
		if($this->hasSamlBackend($group)) {
			return $group;
		}

		$altGid = $this->ownGroupBackend->groupExistsWithDifferentGid($gid);
		if($altGid) {
			return $this->groupManager->get($altGid);
		}

		//FIXME: specific Exception and/or constant error code
		throw new \RuntimeException('Non-migratable duplicate found', 2);
	}

	protected function hasSamlBackend(IGroup $group): bool {
		$reflected = new \ReflectionClass($group);
		$backendsProperty = $reflected->getProperty('backends');
		$backendsProperty->setAccessible(true);
		$backends = $backendsProperty->getValue();
		foreach ($backends as $backend) {
			if($backend instanceof GroupBackend) {
				return true;
			}
		}
		return false;
	}

	public function evaluateGroupMigrations(array $groups) {
		$candidateInfo = $this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, null);
		if($candidateInfo === null) {
			return;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if(!isset($candidateInfo['dropAfter']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->deleteAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
			return;
		}

		$this->jobList->add(MigrateGroups::class, ['gids' => $groups]);
	}
}
