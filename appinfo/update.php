<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@nextcloud.com>
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

$config = \OC::$server->getConfig();
$installedVersion = $config->getAppValue('user_saml', 'installed_version');

// Versions below 1.2.1 use SAML by default for the desktop client, this default
// has been changed with 1.2.1. To not break existing installations the value gets
// manually changed on update.
if (version_compare($installedVersion, '1.2.1', '<')) {
	$config->setAppValue('user_saml', 'general-use_saml_auth_for_desktop', '1');
}

// Versions below 1.2.2 don't have the choice between environment variable or
// native SAML integration as the default was SAML back then.
if (version_compare($installedVersion, '1.2.2', '<')) {
	$config->setAppValue('user_saml', 'type', 'saml');
}
