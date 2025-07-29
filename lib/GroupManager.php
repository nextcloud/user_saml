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
use OCP\Server;
use Psr\Log\LoggerInterface;

class GroupManager {
	public const LOCAL_GROUPS_CHECK_FOR_MIGRATION = 'localGroupsCheckForMigration';
	public const STATE_MIGRATION_PHASE_EXPIRED = 'EXPIRED';

	public function __construct(
		protected IDBConnection $db,
		private readonly IGroupManager $groupManager,
		private readonly GroupBackend $ownGroupBackend,
		private readonly IConfig $config,
		private readonly IEventDispatcher $dispatcher,
		private readonly IJobList $jobList,
		private readonly SAMLSettings $settings,
	) {
	}

	/**
	 * @param string[] $samlGroupNames
	 * @param IGroup[] $assignedGroups
	 * @return string[]
	 */
	private function getGroupsToRemove(array $samlGroupNames, array $assignedGroups): array {
		$groupsToRemove = [];
		foreach ($assignedGroups as $group) {
			Server::get(LoggerInterface::class)->debug('Checking group {group} for removal', ['app' => 'user_saml', 'group' => $group->getGID()]);
			if (in_array($group->getGID(), $samlGroupNames, true)) {
				continue;
			}
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
			Server::get(LoggerInterface::class)->debug('Checking group {group} for addition', ['app' => 'user_saml', 'group' => $groupName]);
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
		$assignedGroupIds = array_map(fn (IGroup $group) => $group->getGID(), $assignedGroups);
		$groupsToRemove = $this->getGroupsToRemove($samlGroupNames, $assignedGroups);
		$groupsToAdd = $this->getGroupsToAdd($samlGroupNames, $assignedGroupIds);
		$this->handleUserUnassignedFromGroups($user, $groupsToRemove);
		$this->handleUserAssignedToGroups($user, $groupsToAdd);
	}

	protected function translateGroupToIds(array &$samlGroups): void {
		array_walk($samlGroups, function (&$gid): void {
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

		// keep empty groups if general-keep_groups is set to 1
		$keepEmptyGroups = $this->config->getAppValue(
			'user_saml',
			'general-keep_groups',
			'0',
		);

		if ($this->hasSamlBackend($group)) {
			$this->ownGroupBackend->removeFromGroup($user->getUID(), $group->getGID());
			if ($keepEmptyGroups !== '1' && $this->ownGroupBackend->countUsersInGroup($gid) === 0) {
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
		} catch (GroupNotFoundException|NonMigratableGroupException) {
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
		if ($migrationAllowListRaw !== '' || $migrationAllowListRaw !== self::STATE_MIGRATION_PHASE_EXPIRED) {
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
		$candidateInfo = $this->getCandidateInfoIfValid();
		if ($candidateInfo === null) {
			return;
		}

		$this->jobList->add(MigrateGroups::class, ['gids' => $groups]);
	}

	protected function isGroupInTransitionList(string $groupId): bool {
		$candidateInfo = $this->getCandidateInfoIfValid();
		if (!$candidateInfo) {
			return false;
		}
		return in_array($groupId, $candidateInfo['groups'], true);
	}

	/**
	 * @return array{dropAfter: int, groups: string[]}|null
	 */
	public function getCandidateInfoIfValid(): ?array {
		$candidateInfo = $this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === '' || $candidateInfo === self::STATE_MIGRATION_PHASE_EXPIRED) {
			return null;
		}
		/** @var array{dropAfter: int, groups: string[]} $candidateInfo */
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || $candidateInfo['dropAfter'] < time()) {
			$this->config->setAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, self::STATE_MIGRATION_PHASE_EXPIRED);
			return null;
		}

		return $candidateInfo;
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
		$isInTransition
			= $group !== null
			&& $group->getGID() !== 'admin'
			&& in_array('Database', $group->getBackendNames())
			&& $this->isGroupInTransitionList($group->getGID());

		if ($isInTransition) {
			Server::get(LoggerInterface::class)->debug('Checking group {group} for foreign members', ['app' => 'user_saml', 'group' => $group->getGID()]);
			$hasOnlySamlUsers = !$this->hasGroupForeignMembers($group);
			Server::get(LoggerInterface::class)->debug('Completed checking group {group} for foreign members', ['app' => 'user_saml', 'group' => $group->getGID()]);
			if (!$hasOnlySamlUsers) {
				$this->updateCandidatePool([$group->getGID()]);
			}
		}
		return $isInTransition && $hasOnlySamlUsers;
	}

	public function updateCandidatePool(array $migratedGroups): void {
		$candidateInfo = $this->config->getAppValue('user_saml', self::LOCAL_GROUPS_CHECK_FOR_MIGRATION, '');
		if ($candidateInfo === '' || $candidateInfo === self::STATE_MIGRATION_PHASE_EXPIRED) {
			return;
		}
		$candidateInfo = \json_decode($candidateInfo, true);
		if (!isset($candidateInfo['dropAfter']) || !isset($candidateInfo['groups'])) {
			return;
		}
		$candidateInfo['groups'] = array_diff($candidateInfo['groups'], $migratedGroups);
		$this->config->setAppValue(
			'user_saml',
			self::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			json_encode($candidateInfo)
		);
	}
}
