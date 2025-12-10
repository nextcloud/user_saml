<!--
 - SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog
All notable changes to this project will be documented in this file.

## 7.1.1

### Fixed

* [Fix\(settings\): Fix missing global attribute (user_saml#1002)](https://github.com/nextcloud/user_saml/pull/1002)

### Dependencies

* [Build\(deps\): bump robrichards/xmlseclibs from 3.1.3 to 3.1.4 (user_saml#1004)](https://github.com/nextcloud/user_saml/pull/1004)
* [Build\(deps\): bump onelogin/php\-saml from 4.3.0 to 4.3.1 (user_saml#1005)](https://github.com/nextcloud/user_saml/pull/1005)

## 7.1.0

### Added

* [Feat(deps): Add Nextcloud 33 support (user_saml#986)](https://github.com/nextcloud/user_saml/pull/986)

### Fixed

* [Fix(Users): add to, not overwrite, order by clause (user_saml#976)](https://github.com/nextcloud/user_saml/pull/976)
* [Fix(LDAP): lookup of AD users through objectGUID (user_saml#995)](https://github.com/nextcloud/user_saml/pull/995)

### Other

* [Chore(CI): Adjust testing matrix for Nextcloud 32 on master (user_saml#985)](https://github.com/nextcloud/user_saml/pull/985)
* [Chore(migration): Port away from deprecated IQueryBuilder::execute (user_saml#988)](https://github.com/nextcloud/user_saml/pull/988)
* [Chore(i18n):  Remove trailing space (user_saml#993)](https://github.com/nextcloud/user_saml/pull/993)

### Dependencies
* [Build(deps-dev): bump guzzlehttp/guzzle from 7.9.3 to 7.10.0 in /tests/integration (user_saml#983)](https://github.com/nextcloud/user_saml/pull/983)
* [Ci: Update github actions (user_saml#989)](https://github.com/nextcloud/user_saml/pull/989)

## 7.0.0

### Added

* [Feat: Initiate login to IdP via POST (user_saml#861)](https://github.com/nextcloud/user_saml/pull/861)
* [Feat: add saml:user:add command to pre-provision users (user_saml#962)](https://github.com/nextcloud/user_saml/pull/962)

### Removed

* [Drop support for NC 28+29, requires PHP >= 8.1, update deps, add rector (user_saml#950)](https://github.com/nextcloud/user_saml/pull/950)

### Fixed

* [Chore(i18n): Changed spelling of entity ID (user_saml#953)](https://github.com/nextcloud/user_saml/pull/953)
* [Fix(env-mode): accept multiple comma-separated groups (user_saml#954)](https://github.com/nextcloud/user_saml/pull/954)
* [Fix(Settings): do not fail badly on unknown keys (user_saml#955)](https://github.com/nextcloud/user_saml/pull/955)
* [Fix: do not show login options on env mode (there is just one) (user_saml#956)](https://github.com/nextcloud/user_saml/pull/956)
* [Fix(Resolver): replace iconv (user_saml#967)](https://github.com/nextcloud/user_saml/pull/967)

### Other

* [Chore(tests): Cleanup bootstrap.php to be forward-compatible (user_saml#961)](https://github.com/nextcloud/user_saml/pull/961)
* [Chore: update codeowners (user_saml#979)](https://github.com/nextcloud/user_saml/pull/979)

### Dependencies

* [Build(deps): bump onelogin/php-saml from 4.2.0 to 4.3.0 (user_saml#965)](https://github.com/nextcloud/user_saml/pull/965)
* [Build(deps-dev): bump nextcloud/coding-standard from 1.3.2 to 1.4.0 in /vendor-bin/cs-fixer (user_saml#971)](https://github.com/nextcloud/user_saml/pull/971)
* [Ci: update reuse.yml workflow from template (user_saml#972)](https://github.com/nextcloud/user_saml/pull/972)

## 6.6.0

### Added

* [Feat(keepEmptyGroups): Add app configuration parameter to keep empty groups (user_saml#911)](https://github.com/nextcloud/user_saml/pull/911)
* [Feat(settings): optional config option for sp entityId (user_saml#932)](https://github.com/nextcloud/user_saml/pull/932)

### Under the hood

* [Cleanup app bootstrap (user_saml#611)](https://github.com/nextcloud/user_saml/pull/611)
* [Fix: Register the dav plugin through an event listener (user_saml#943)](https://github.com/nextcloud/user_saml/pull/943)

## 6.5.0

### Added

* [Feat(deps): Add Nextcloud 32 support (user_saml#921)](https://github.com/nextcloud/user_saml/pull/921)

### Fixed

* [Fix(UI): set value to the require_provisioned_account option also in env mode (user_saml#916)](https://github.com/nextcloud/user_saml/pull/916)
* [Fix(GroupMigration): issue debug info on why a group is not being migrated (user_saml#923)](https://github.com/nextcloud/user_saml/pull/923)

### Dependencies

* [Build(deps): bump firebase/php-jwt from 6.10.2 to 6.11.0 (user_saml#924)](https://github.com/nextcloud/user_saml/pull/924)

### Under the hood

* [Chore(CI): Adjust testing matrix for Nextcloud 31 on master (user_saml#920)](https://github.com/nextcloud/user_saml/pull/920)
* [Ci(integration): also test against PHP 8.4 (user_saml#922)](https://github.com/nextcloud/user_saml/pull/922)

## 6.4.1

### Fixed

* [Handle LoginException when authenticating with Apache (user_saml#910)](https://github.com/nextcloud/user_saml/pull/910)

## 6.4.0

### Added

* [Feat(groups): add setting display name to group backend (user_saml#855)](https://github.com/nextcloud/user_saml/pull/855)
* [Feat(PassthroughParameters): Make it possible to pass through parameters to the SAML library (user_saml#901)](https://github.com/nextcloud/user_saml/pull/901)

### Fixed

* [Fix(settings): Fix settings name (user_saml#903)](https://github.com/nextcloud/user_saml/pull/903)
* [Fix(Controller): make redirectUrl optional (user_saml#905)](https://github.com/nextcloud/user_saml/pull/905)
* [Fix(UI): fix usage of $.ajax by going VanillaJS (user_saml#913)](https://github.com/nextcloud/user_saml/pull/913)

### Dependencies

* [Build(deps): bump firebase/php-jwt from 6.10.1 to 6.10.2 in /3rdparty (user_saml#908)](https://github.com/nextcloud/user_saml/pull/908)
* [Deps(php-saml): apply PHP 8.4 compat patch (user_saml#912)](https://github.com/nextcloud/user_saml/pull/912)

### Under the hood

* [Refactor: small adjustments not impacting functionalities (user_saml#896)](https://github.com/nextcloud/user_saml/pull/896)
* [Build(deps-dev): bump nextcloud/coding-standard from 1.3.1 to 1.3.2 (user_saml#899)](https://github.com/nextcloud/user_saml/pull/899)
* [Build(deps-dev): bump behat/behat from 3.14.0 to 3.15.0 in /tests/integration (user_saml#900)](https://github.com/nextcloud/user_saml/pull/900)* [Build(deps-dev): bump nextcloud/coding-standard from 1.3.1 to 1.3.2 (user_saml#899)](https://github.com/nextcloud/user_saml/pull/899)
* [Refactor: Apply code best practices PHP8+ (user_saml#907)](https://github.com/nextcloud/user_saml/pull/907)
* [Build(deps): cleanup composer (user_saml#909)](https://github.com/nextcloud/user_saml/pull/909)

## 6.3.0

### Added

* [Feat(deps): Add Nextcloud 31 support (user_saml#875)](https://github.com/nextcloud/user_saml/pull/875)
* [Migrate REUSE to toml format (user_saml#881)](https://github.com/nextcloud/user_saml/pull/881)
* [Fix: Add more logging to the saml/acs endpoint (user_saml#885)](https://github.com/nextcloud/user_saml/pull/885)
* [Fix: Log attribute updates (user_saml#886)](https://github.com/nextcloud/user_saml/pull/886)
* [Debug(Groups): log group handling upon login (user_saml#889)](https://github.com/nextcloud/user_saml/pull/889)

### Fixed

* [Fix: Fire UserChangedEvent only after change happened (user_saml#873)](https://github.com/nextcloud/user_saml/pull/873)
* [Fix(Groups): drop groups with mixed users from transition list (user_saml#888)](https://github.com/nextcloud/user_saml/pull/888)
* [Fix(command): Ensure that writeln() argument is string (user_saml#893)](https://github.com/nextcloud/user_saml/pull/893)

### Dependencies

* [Build(deps-dev): bump guzzlehttp/guzzle from 7.8.1 to 7.9.1 in /tests/integration (user_saml#868)](https://github.com/nextcloud/user_saml/pull/868)
* [Build(deps-dev): bump guzzlehttp/guzzle from 7.9.1 to 7.9.2 in /tests/integration (user_saml#869)](https://github.com/nextcloud/user_saml/pull/869)
* [Build(deps-dev): bump nextcloud/coding-standard from 1.2.1 to 1.2.3 (user_saml#878)](https://github.com/nextcloud/user_saml/pull/878)
* [Build(deps-dev): bump psalm/phar from 5.25.0 to 5.26.0 (user_saml#883)](https://github.com/nextcloud/user_saml/pull/883)
* [Build(deps-dev): bump psalm/phar from 5.26.0 to 5.26.1 (user_saml#884)](https://github.com/nextcloud/user_saml/pull/884)
* [Build(deps-dev): bump nextcloud/coding-standard from 1.2.3 to 1.3.1 (user_saml#891)](https://github.com/nextcloud/user_saml/pull/891)

### Other

* [Chore: update workflows from templates (user_saml#809)](https://github.com/nextcloud/user_saml/pull/809)
* [Chore(CI): Adjust testing matrix for Nextcloud 30 on master (user_saml#874)](https://github.com/nextcloud/user_saml/pull/874)

## 6.2.0

### Added

* [Feat(deps): Add Nextcloud 30 support (user_saml#827)](https://github.com/nextcloud/user_saml/pull/827)
* [Add SPDX header (user_saml#841)](https://github.com/nextcloud/user_saml/pull/841)

### Fixed

* [Fix: remove long-unused desktop option (user_saml#690)](https://github.com/nextcloud/user_saml/pull/690)
* [Fix(Groups): take other DB errors into consideration (user_saml#839)](https://github.com/nextcloud/user_saml/pull/839)
* [Feat: migrate from deprecated PublicEmitter to IEventDispatcher (user_saml#856)](https://github.com/nextcloud/user_saml/pull/856)
* [Fix(Groups): take over members during migration (user_saml#863)](https://github.com/nextcloud/user_saml/pull/863)
* [Perf(db): Avoid double querying (user_saml#834)](https://github.com/nextcloud/user_saml/pull/834)

### Dependencies

* [Build(deps): bump firebase/php-jwt from 6.8.1 to 6.10.0 in /3rdparty (user_saml#844)](https://github.com/nextcloud/user_saml/pull/844)
* [Build(deps): bump firebase/php-jwt from 6.10.0 to 6.10.1 in /3rdparty (user_saml#852)](https://github.com/nextcloud/user_saml/pull/852)
* [Build(deps): bump onelogin/php-saml from 4.1.0 to 4.2.0 in /3rdparty (user_saml#854)](https://github.com/nextcloud/user_saml/pull/854)

## 6.1.3

### Fixed

* [Fix(Login): do not advertise CHECK_PASSWORD capability (user_saml#829)](https://github.com/nextcloud/user_saml/pull/829)

## 6.1.2

### Fixed

* [Fix(UI): permanent labels for input elements (user_saml#816)](https://github.com/nextcloud/user_saml/pull/816)
* [Fix(UI): auth provider picker did not react (user_saml#817)](https://github.com/nextcloud/user_saml/pull/817)
* [Fix(User): load timezone handling related resources (user_saml#819)](https://github.com/nextcloud/user_saml/pull/819)
* [Fix(UI): readable login dropdown chooser on dark mode (user_saml#820)](https://github.com/nextcloud/user_saml/pull/820)

## 6.1.1

### Fixed

- [Fix: keep local groups when there is no group mapping set up (user_saml#806)](https://github.com/nextcloud/user_saml/pull/806)

## 6.1.0

### Added

- [Group backend and migration of original SAML groups created as local database groups (user_saml#622)](https://github.com/nextcloud/user_saml/pull/622)

### Fixed

- [Fix: Also create skeleton for users from environment based auth (user_saml#797)](https://github.com/nextcloud/user_saml/pull/797)

## 6.0.1

### Added 

- [Feat(dep): NC 29 comaptibility (user_saml#787)](https://github.com/nextcloud/user_saml/pull/787)

### Changed

- tranlsation updates
- [Refactor(Controller): read parameter only once (user_saml#788)](https://github.com/nextcloud/user_saml/pull/788)

## 6.0.0

### Added

- Added support for Nextcloud 28

### Removed

- Dropped support for Nextcloud 25-27

### Fixed

- do not hardcode IdP to 1 on redirect (#769)
- Implement IGetDisplayNameBackend (#771)
- Do not try to validate metadata for environment variable config (#774)
- remove deprecated event and class (#779)

## 5.2.2

### Fixed
- Fix validating SAML XML schemas (user_saml#754)

### Changed
- Dependency updates

## 5.2.1

### Fixed
- Avoid infinite redirection for disabled user (user_saml#717)
- Better distinguish admin sections (user_saml#730)
- Ensure $configurations is an array (user_saml#734)

### Changed
- Dependency updates

## 5.2.0

### Added
- MFA verification flag (user_saml#668)

### Changed
- L10n: Change to uppercase (user_saml#691)
- Bump to v5.2 and require at least NC 25 (user_saml#705)
- Extract idp from jwt in globalscale (user_saml#714)
- Dependency updates

## 5.1.2

### Fixed
- gently handle incoming SAML Logout Request when the session is missing

## 5.1.1

### Fixed
- Use session locking to be compatible with Nextcloud 25 during logout

## 5.1.0

### Added
- User filtering by group memberships

### Fixed
- fetching metadata with IdP id 1
- Spelling consolidation

## 5.0.3
### Fixed
- Fix signining in with multiple IdPs
- Do not show config chooser when operating in env mode
### Changed
- Various dependency updates

## 5.0.2
### Fixed
- Fix setup with only one idp by using 1 as default value in routes
- Fix executing meta data validation check after configuration change

## 5.0.1
### Fixed
- Direct login silently fails under some circumstances
- Mobile login shows regular web interfaces instead of Grant Access page
- Global checkboxes always unticked in SAML settings

## 5.0.0
### Changed
- store configurations in a separate database table, not appconfig

### Added
- occ commands for modifying SAML configurations

### Removed
- Ability to change SAML configuration with occ app-config, use the new occ commands instead

### Fixed
- Use effective uid for autoprovisioning new users
- Handle mobile login flow with direct=1
- Set proper relaystate url

## 4.1.1

### Added
- Add logging for SLO errors
- sanitize and test user id received from IdP, if original does not match
- Allow setting of "retrieveParametersFromServer

## 4.1.0
### Added
- Nextcloud 22 support

### Fixed
- logins with base64 resembling UIDs

## 4.0.0
### Removed
- Nextcloud <21 support

## 3.3.3
### Added
- Possibility to add custom direct login message

## 3.3.2
### Added
- Possible url for SLO response

### Fixed
- Fix login flow support yet again
- Buton colors
- Translations
- Fixed provisioning users from encoded uids
- Fix missing IDP variable

## 3.3.1
### Fixed
- 21 suport
- login flow support with strict cookies

## 3.1.2
### Fixed
- 19 support. This was broken due to stricter cookies

## 3.0.1
### Added
- Add setting to specify a different signature algorithm #401

### Changed
- translation updates

## 3.0.0
### Changed
- fixed login with chrome browser #379
- translation updates
- Make 19 compatible #380

## 2.4.0
### Added
- IdP initiated logout

### Fixed
- No password confirmation for passwordless users
- Handle exceptions more graceful (prevent app from disabling)
- Desktop client login failing in some cases

## 2.3.1
### Fixed
- name id format is set per provider

## 2.3.0

### Added
- Ability to specify nameformat when configuring IdPs
- Properly set the timezone

### Changed
- Also search for diplayname and email in backend
- Bumped onelogin/php-saml to 3.1.1
- Updated translations

### Fixed
- Catch exception so app does not get disabled on random PUT requests

## 2.2.0

### Changed

- Update dependencies for PHP 7.3 compatibility
- Ready for Nextcloud 16
- improve logging


## 2.1.1

### Changed

- sort IDP's alphabetical
- improved documentation in UI, add hint for direct login URL

### Fixed

- create skeleton files if SAML is used in combination with LDAP

## 2.1.0

### Changed

- add attribute mapping for the users home directory when creating a new user
- use a combobox to select the IDP on login when more then 4 IDPs are configured
- improved debug logging and in case of errors
- Add sabredav plugin to register environment auth for dav requests

### Fixed

- remove trailing and leading spaces on settings
- adjust login page to the theme

## 2.0.0

### Changed

- update to upstream php-saml 3.0 (upstream library) which removes the mcrypt dependency
- Improve SAML behaviour in a Global Scale setup


## 1.7.0

### Changed

- many small changes/fixes to make SAML work in a Global Scale setup

## 1.6.2

### Changed

- Add reset button to start over with the configuration
- Show default login screen until SSO is configured
- updated translations

### Fixed

- small fixes

## 1.6.1

### Fixed

- internal version number

## 1.6.0

### Changed

- Allow multiple IDP's
- Add attribute mapping for groups

## 1.5.0

### Changed

- add attribute mapping for the users quota
- add option to use the local user back-end (and LDAP) in parallel to SAML

### Fixed

- fix redirect loop in case a user was disabled
- query LDAP for user data during auto-provisioning in case "Only allow authentication if an account is existent on some other backend" is enabled

## 1.4.2

- update display name in accounts table correctly
- improve error messages and logging

## 1.4.0

### Fixed

- Spelling mistakes
- Keep displayname after login
- Fix compatibility with reverse proxies
- Set last login after successful login operation
- SLO support
- Hide attribute mapping until a type is selected

## 1.3.2

### Added

- Added sample screenshots
