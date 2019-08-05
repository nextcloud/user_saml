<?php
/**
 * @copyright Copyright (c) 2019 Dominik Ach <da@infodatacom.de>
 *
 * @author Dominik Ach <da@infodatacom.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Maximilian Ruta <mr@xtain.net>
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Giuliano Mele <giuliano.mele@verdigado.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML;

use OC\Hooks\PublicEmitter;
use OCA\User_SAML\Exceptions\GroupNotFoundException;
use OCA\User_SAML\Exceptions\NonMigratableGroupException;
use OCA\User_SAML\Jobs\MigrateGroups;
use OCP\BackgroundJob\IJobList;
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
	/** @var IJobList */
	private $jobList;
	/** @var SAMLSettings */
	private $settings;


	public function __construct(
		IDBConnection $db,
		IGroupManager $groupManager,
		GroupBackend $ownGroupBackend,
		IConfig $config,
		IJobList $jobList,
		SAMLSettings $settings
	) {
		$this->db = $db;
		$this->groupManager = $groupManager;
		$this->ownGroupBackend = $ownGroupBackend;
		$this->config = $config;
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
		$this->updateUserGroups($user, $samlGroupNames);
		// TODO: drop following line with dropping NC 28 support
		$this->evaluateGroupMigrations($samlGroupNames);
	}

	public function updateUserGroups(IUser $user, array $samlGroupNames): void {
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
				/** @psalm-suppress UndefinedInterfaceMethod */
				$this->groupManager->emit('\OC\Group', 'preDelete', [$group]);
				$this->ownGroupBackend->deleteGroup($group->getGID());
				/** @psalm-suppress UndefinedInterfaceMethod */
				$this->groupManager->emit('\OC\Group', 'postDelete', [$group]);
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
		} catch (GroupNotFoundException $e) {
			$group = $this->createGroupInBackend($gid);
		} catch (NonMigratableGroupException $e) {
			$providerId = $this->settings->getProviderId();
			$settings = $this->settings->get($providerId);
			$groupPrefix = $settings['saml-attribute-mapping-group_mapping_prefix'] ?? SAMLSettings::DEFAULT_GROUP_PREFIX;
			$group = $this->createGroupInBackend($groupPrefix . $gid, $gid);
		}

		$group->addUser($user);
	}

	protected function createGroupInBackend(string $gid, ?string $originalGid = null): ?IGroup {
		if ($this->groupManager instanceof PublicEmitter) {
			$this->groupManager->emit('\OC\Group', 'preCreate', array($gid));
		}
		if (!$this->ownGroupBackend->createGroup($gid, $originalGid ?? $gid)) {
			return null;
		}

		$group = $this->groupManager->get($gid);
		if ($this->groupManager instanceof PublicEmitter) {
			$this->groupManager->emit('\OC\Group', 'postCreate', array($group));
		}

		return $group;
	}

	/**
	 * @throws GroupNotFoundException|NonMigratableGroupException
	 */
	protected function findGroup(string $gid): IGroup {
		$migrationAllowList = $this->config->getAppValue(
			'user_saml',
			GroupManager::LOCAL_GROUPS_CHECK_FOR_MIGRATION,
			''
		);
		$strictBackendCheck = '' === $migrationAllowList;
		if ($migrationAllowList !== '') {
			$migrationAllowList = \json_decode($migrationAllowList, true);
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

	public function evaluateGroupMigrations(array $groups): void {
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
