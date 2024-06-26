<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML;

use OCA\User_SAML\Exceptions\GroupNotFoundException;
use OCA\User_SAML\Exceptions\NonMigratableGroupException;
use OCA\User_SAML\Jobs\MigrateGroups;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;

class GroupManager {
	public const LOCAL_GROUPS_CHECK_FOR_MIGRATION = 'localGroupsCheckForMigration';

	/**
	 * @var IDBConnection $db
	 */
	protected $db;

	/** @var IGroupManager */
	private $groupManager;
	/** @var GroupBackend */
	private $ownGroupBackend;
	/** @var IConfig */
	private $config;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var IJobList */
	private $jobList;
	/** @var SAMLSettings */
	private $settings;


	public function __construct(
		IDBConnection $db,
		IGroupManager $groupManager,
		GroupBackend $ownGroupBackend,
		IConfig $config,
		IEventDispatcher $dispatcher,
		IJobList $jobList,
		SAMLSettings $settings
	) {
		$this->db = $db;
		$this->groupManager = $groupManager;
		$this->ownGroupBackend = $ownGroupBackend;
		$this->config = $config;
		$this->dispatcher = $dispatcher;
		$this->jobList = $jobList;
		$this->settings = $settings;
	}

	/**
	 * @param string[] $samlGroupNames
	 * @param IGroup[] $assignedGroups
	 * @return string[]
	 */
	private function getGroupsToRemove(array $samlGroupNames, array $assignedGroups): array {
		$groupsToRemove = [];
		// FIXME: Seems unused
		$this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		foreach ($assignedGroups as $group) {
			// if group is not supplied by SAML and group has SAML backend
			if (!in_array($group->getGID(), $samlGroupNames) && $this->hasSamlBackend($group)) {
				$groupsToRemove[] = $group->getGID();
			} elseif ($this->mayModifyGroup($group)) {
				$groupsToRemove[] = $group->getGID();
			}
		}
		return $groupsToRemove;
	}

	/**
	 * @param string[] $samlGroupNames
	 * @param string[] $assignedGroupIds
	 * @return string[]
	 */
	private function getGroupsToAdd(array $samlGroupNames, array $assignedGroupIds): array {
		$groupsToAdd = [];
		foreach ($samlGroupNames as $groupName) {
			$group = $this->groupManager->get($groupName);
			// if user is not assigned to the group or the provided group has a non SAML backend
			if (!in_array($groupName, $assignedGroupIds) || !$this->hasSamlBackend($group)) {
				$groupsToAdd[] = $groupName;
			} elseif ($this->mayModifyGroup($group)) {
				$groupsToAdd[] = $group->getGID();
			}
		}
		return $groupsToAdd;
	}

	public function handleIncomingGroups(IUser $user, array $samlGroupNames): void {
		$id = $this->settings->getProviderId();
		$groupMapping = $this->settings->get($id)['saml-attribute-mapping-group_mapping'] ?? null;
		if ($groupMapping === null || $groupMapping === '') {
			// When no group mapping is set up, it is not our business
			return;
		}

		$this->updateUserGroups($user, $samlGroupNames);
		// TODO: drop following line with dropping NC 28 support
		$this->evaluateGroupMigrations($samlGroupNames);
	}

	protected function updateUserGroups(IUser $user, array $samlGroupNames): void {
		$this->translateGroupToIds($samlGroupNames);
		$assignedGroups = $this->groupManager->getUserGroups($user);
		$assignedGroupIds = array_map(function (IGroup $group) {
			return $group->getGID();
		}, $assignedGroups);
		$groupsToRemove = $this->getGroupsToRemove($samlGroupNames, $assignedGroups);
		$groupsToAdd = $this->getGroupsToAdd($samlGroupNames, $assignedGroupIds);
		$this->handleUserUnassignedFromGroups($user, $groupsToRemove);
		$this->handleUserAssignedToGroups($user, $groupsToAdd);
	}

	protected function translateGroupToIds(array &$samlGroups): void {
		array_walk($samlGroups, function (&$gid) {
			$altGid = $this->ownGroupBackend->groupExistsWithDifferentGid($gid);
			if ($altGid !== null) {
				$gid = $altGid;
			}
		});
	}

	protected function handleUserUnassignedFromGroups(IUser $user, array $groupIds): void {
		foreach ($groupIds as $gid) {
			$this->unassignUserFromGroup($user, $gid);
		}
	}

	protected function unassignUserFromGroup(IUser $user, string $gid): void {
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			return;
		}

		if ($this->hasSamlBackend($group)) {
			$this->ownGroupBackend->removeFromGroup($user->getUID(), $group->getGID());
			if ($this->ownGroupBackend->countUsersInGroup($gid) === 0) {
				$this->dispatcher->dispatchTyped(new BeforeGroupDeletedEvent($group));
				$this->ownGroupBackend->deleteGroup($group->getGID());
				$this->dispatcher->dispatchTyped(new GroupDeletedEvent($group));
			}
		} else {
			$group->removeUser($user);
		}
	}

	protected function handleUserAssignedToGroups(IUser $user, $groupIds): void {
		foreach ($groupIds as $gid) {
			$this->assignUserToGroup($user, $gid);
		}
	}

	protected function assignUserToGroup(IUser $user, string $gid): void {
		try {
			$group = $this->findGroup($gid);
		} catch (GroupNotFoundException|NonMigratableGroupException $e) {
			$providerId = $this->settings->getProviderId();
			$settings = $this->settings->get($providerId);
			$groupPrefix = $settings['saml-attribute-mapping-group_mapping_prefix'] ?? SAMLSettings::DEFAULT_GROUP_PREFIX;
			$group = $this->createGroupInBackend($groupPrefix . $gid, $gid);
		}

		$group->addUser($user);
	}

	protected function createGroupInBackend(string $gid, ?string $originalGid = null): ?IGroup {
		$this->dispatcher->dispatchTyped(new BeforeGroupCreatedEvent($gid));
		if (!$this->ownGroupBackend->createGroup($gid, $originalGid ?? $gid)) {
			return null;
		}

		$group = $this->groupManager->get($gid);
		$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));

		return $group;
	}

	/**
	 * @throws GroupNotFoundException|NonMigratableGroupException
	 */
	protected function findGroup(string $gid): IGroup {
		$migrationAllowListRaw = $this->config->getAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			''
		);
		$strictBackendCheck = $migrationAllowListRaw === '';

		$migrationAllowList = null;
		if ($migrationAllowListRaw !== '') {
			/** @var array{dropAfter: int, groups: string[]} $migrationAllowList */
			$migrationAllowList = \json_decode($migrationAllowListRaw, true);
		}

		if (!$strictBackendCheck && in_array($gid, $migrationAllowList['groups'] ?? [], true)) {
			$group = $this->groupManager->get($gid);
			if ($group === null) {
				throw new GroupNotFoundException();
			}
			return $group;
		}
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			throw new GroupNotFoundException();
		}
		if ($this->hasSamlBackend($group)) {
			return $group;
		}

		$altGid = $this->ownGroupBackend->groupExistsWithDifferentGid($gid);
		if ($altGid) {
			$group = $this->groupManager->get($altGid);
			if ($group) {
				return $group;
			}
			throw new GroupNotFoundException();
		}

		throw new NonMigratableGroupException();
	}

	protected function hasSamlBackend(IGroup $group): bool {
		return in_array('user_saml', $group->getBackendNames());
	}

	protected function evaluateGroupMigrations(array $groups): void {
		$candidateInfo = $this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === '') {
			return;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->deleteAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
			return;
		}

		$this->jobList->add(MigrateGroups::class, ['gids' => $groups]);
	}

	protected function isGroupInTransitionList(string $groupId): bool {
		$candidateInfo = $this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === '') {
			return false;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->deleteAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION);
			return false;
		}

		return in_array($groupId, $candidateInfo['groups']);
	}

	protected function hasGroupForeignMembers(IGroup $group): bool {
		foreach ($group->getUsers() as $user) {
			if ($user->getBackendClassName() !== 'user_saml') {
				return true;
			}
		}
		return false;
	}

	/**
	 * In the transition phase, update group memberships of local groups only
	 * under very specific conditions. Otherwise, membership modifications are
	 * allowed only for groups owned by the SAML backend.
	 */
	protected function mayModifyGroup(?IGroup $group): bool {
		return
			$group !== null
			&& $group->getGID() !== 'admin'
			&& in_array('Database', $group->getBackendNames())
			&& $this->isGroupInTransitionList($group->getGID())
			&& !$this->hasGroupForeignMembers($group);
	}
}
