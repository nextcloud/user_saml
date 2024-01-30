# Changelog
All notable changes to this project will be documented in this file.

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
