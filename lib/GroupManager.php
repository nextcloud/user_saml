<?php

namespace OCA\User_SAML;

use OC\BackgroundJob\JobList;
use OC\Hooks\PublicEmitter;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\Jobs\MigrateGroups;
use OCA\User_SAML\SAMLSettings;
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
	/** @var SAMLSettings */
	private $settings;


	public function __construct(
		IDBConnection $db,
		GroupDuplicateChecker $duplicateChecker,
		IGroupManager $groupManager,
		IUserManager $userManager,
		GroupBackend $ownGroupBackend,
		IConfig $config,
		JobList $jobList,
		SAMLSettings $settings
	) {
		$this->db = $db;
		$this->duplicateChecker = $duplicateChecker;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->ownGroupBackend = $ownGroupBackend;
		$this->config = $config;
		$this->jobList = $jobList;
		$this->settings = $settings;
	}

	private function getGroupsToRemove(array $samlGroups, array $assignedGroups): array {
		$groupsToRemove = [];
		foreach($assignedGroups as $group) {
			// if group is not supplied by SAML and group has SAML backend
			if (!in_array($group->getGID(), $samlGroups) && $this->hasSamlBackend($group)) {
				$groupsToRemove[] = $group->getGID();
			}
		}
		return $groupsToRemove;
	}

	private function getGroupsToAdd(array $samlGroups, array $assignedGroupIds): array {
		$groupsToAdd = [];
		foreach($samlGroups as $group) {
			// if user is not assigend to the group or the provided group has a non SAML backend
			if (!in_array($group, $assignedGroupIds) || !$this->hasSamlBackend($this->groupManager->get($group))) {
				$groupsToAdd[] = $group;
			}
		}
		return $groupsToAdd;
	}

	public function replaceGroups(string $uid, array $samlGroups): void {
		$user = $this->userManager->get($uid);
		if($user === null) {
			return;
		}
		$this->translateGroupToIds($samlGroups);
		$assignedGroups = $this->groupManager->getUserGroups($user);
		$assignedGroupIds = array_map(function(IGroup $group){
			return $group->getGID();
		}, $assignedGroups);
		$groupsToRemove = $this->getGroupsToRemove($samlGroups, $assignedGroups);
		$groupsToAdd = $this->getGroupsToAdd($samlGroups, $assignedGroupIds);
		$this->removeGroups($user, $groupsToRemove);
		$this->addGroups($user, $groupsToAdd);
	}

	protected function translateGroupToIds(array &$samlGroups): void {
		array_walk($samlGroups, function (&$gid){
			$altGid = $this->ownGroupBackend->groupExistsWithDifferentGid($gid);
			if($altGid !== null) {
				$gid = $altGid;
			}
		});
	}

	public function removeGroups(IUser $user, array $groupIds): void {
		foreach ($groupIds as $gid) {
			$this->removeGroup($user, $gid);
		}
	}

	public function removeGroup(IUser $user, string $gid): void {
		$group = $this->groupManager->get($gid);
		if($group === null) {
			return;
		}
		$this->ownGroupBackend->removeFromGroup($user->getUID(), $group->getGID());
		if ($this->ownGroupBackend->countUsersInGroup($gid) === 0) {
			$this->ownGroupBackend->deleteGroup($group->getGID());
		}
	}

	public function addGroups(IUser $user, $groupIds): void {
		foreach ($groupIds as $gid) {
			$this->addGroup($user, $gid);
		}
	}

	public function addGroup(IUser $user, string $gid): void {
		try {
			$group = $this->findGroup($gid);
		} catch (\RuntimeException $e) {
			if($e->getCode() === 1) {
				$group = $this->createGroupInBackend($gid);
			} else if($e->getCode() === 2) {
				//FIXME: probably need config flag. Previous to 17, gid was used as displayname
				$idpPrefix = $this->settings->getPrefix('saml-attribute-mapping-group_mapping_prefix');
				$groupPrefix = $this->config->getAppValue('user_saml', $idpPrefix . 'saml-attribute-mapping-group_mapping_prefix', 'SAML_');
				$group = $this->createGroupInBackend($groupPrefix . $gid, $gid);
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
		if ($migrationWhiteList !== null) {
			$migrationWhiteList = \json_decode($migrationWhiteList, true);
		}
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
		$reflected = new \ReflectionObject($group);
		$backendsProperty = $reflected->getProperty('backends');
		$backendsProperty->setAccessible(true);
		$backends = $backendsProperty->getValue($group);
		// available at nextcloud 22
		// $backends = $group->getBackendNames();
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
