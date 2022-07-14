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

use Test\TestCase;
use \OCA\User_SAML\GroupBackend;

/**
 * @group DB
 */
class GroupBackendTest extends TestCase {

    /** @var GroupBackend */
    private static $groupBackend;
    private static $users = [
        [
            'uid' => 'user_saml_integration_test_uid1',
            'groups' => [
                'user_saml_integration_test_gid1',
                'SAML_user_saml_integration_test_gid2'
            ]
        ],
        [
            'uid' => 'user_saml_integration_test_uid2',
            'groups' => [
                'user_saml_integration_test_gid1'
            ]
        ]
    ];
    private static $groups = [
        [
            'gid' => 'user_saml_integration_test_gid1',
            'saml_gid' => 'user_saml_integration_test_gid1',
            'members' => [
                'user_saml_integration_test_uid1',
                'user_saml_integration_test_uid2'
            ],
            'saml_gid_exists' => true
        ],
        [
            'gid' => 'SAML_user_saml_integration_test_gid2',
            'saml_gid' => 'user_saml_integration_test_gid2',
            'members' => [
                'user_saml_integration_test_uid1'
            ],
            'saml_gid_exists' => false
        ],
        [
            'gid' => 'user_saml_integration_test_gid3',
            'saml_gid' => 'user_saml_integration_test_gid3',
            'members' => [],
            'saml_gid_exists' => true
        ],
    ];

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$groupBackend = new \OCA\User_SAML\GroupBackend(\OC::$server->getDatabaseConnection());
        foreach(self::$groups as $group){
            self::$groupBackend->createGroup($group['gid'], $group['saml_gid']);
        }
        foreach(self::$users as $user){
            foreach($user['groups'] as $group){
                self::$groupBackend->addToGroup($user['uid'], $group);
            }
        }
    }

    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
        self::$groupBackend = new \OCA\User_SAML\GroupBackend(\OC::$server->getDatabaseConnection());
        foreach(self::$users as $user){
            foreach($user['groups'] as $group){
                self::$groupBackend->removeFromGroup($user['uid'], $group);
            }
        }
        foreach (self::$groups as $group) {
            self::$groupBackend->deleteGroup($group['gid']);
        }
    }

    public function testInGroup() {
        foreach(self::$groups as $group){
            foreach(self::$users as $user){
                $result = self::$groupBackend->inGroup($user['uid'], $group['gid']);
                if(in_array($group['gid'], $user['groups'])){
                    $this->assertTrue($result, sprintf("User %s should be member of group %s", $user['uid'], $group['gid']));
                } else {
                    $this->assertFalse($result, sprintf("User %s should not be member of group %s", $user['uid'], $group['gid']));
                }
            }
        }
    }

    public function testGetGroups() {
        $groups = self::$groupBackend->getGroups();
        foreach (self::$groups as $group) {
            $this->assertContains($group['gid'], $groups, sprintf('Group %s should be retrieved', $group['gid']));
        }
    }

    public function testGetUserGroups() {
        foreach(self::$users as $user){
            $userGroups = self::$groupBackend->getUserGroups($user['uid']);
            $this->assertCount(count($user['groups']), $userGroups, 'Should retrieve all user groups');
            foreach($userGroups as $userGroup){
                $this->assertContains($userGroup, $user['groups'], sprintf('Users %s should be member of groups %s', $user['uid'], $userGroup));
            }
        }
    }

    public function testGroupExists() {
        foreach(self::$groups as $group){
            $result = self::$groupBackend->groupExists($group['saml_gid']);
            $this->assertSame($group['saml_gid_exists'], $result, sprintf('Group %s should exist', $group['saml_gid']));
        }
    }

    public function testUsersInGroups() {
        foreach(self::$groups as $group){
            $users = self::$groupBackend->usersInGroup($group['gid']);
            $this->assertCount(count($group['members']), $users, 'Should retrieve all group members');
            foreach($users as $user){
                $this->assertContains($user, $group['members'], sprintf('User %s should be member of group %s', $user, $group['gid']));
            }
        }
    }
}