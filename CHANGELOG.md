# Changelog
All notable changes to this project will be documented in this file.

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
