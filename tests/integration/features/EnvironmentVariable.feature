Feature: EnvironmentVariable

  Scenario: Authenticating using environment variable with SSO and no check if user exists on backend
    And The setting "type" is set to "environment-variable"
    And The setting "general-uid_mapping" is set to "REMOTE_USER"
    And The setting "saml-attribute-mapping-group_mapping" is set to "REMOTE_GROUPS"
    And The environment variable "REMOTE_USER" is set to "not-provisioned-user"
    And The environment variable "REMOTE_GROUPS" is set to "Department A,Team B"
    When I send a GET request to "http://localhost:8080/index.php/login"
    Then I should be redirected to "http://localhost:8080/index.php/apps/dashboard/"
    Then The user value "id" should be "not-provisioned-user"
    And The last login timestamp of "not-provisioned-user" should not be empty
    And User "not-provisioned-user" is part of the groups "SAML_Department A, SAML_Team B"

  Scenario: Authenticating using environment variable with SSO and successful check if user exists on backend
    Given A local user with uid "provisioned-user" exists
    And The setting "type" is set to "environment-variable"
    And The setting "general-require_provisioned_account" is set to "1"
    And The setting "general-uid_mapping" is set to "REMOTE_USER"
    And The environment variable "REMOTE_USER" is set to "provisioned-user"
    When I send a GET request to "http://localhost:8080/index.php/login"
    Then I should be redirected to "http://localhost:8080/index.php/apps/dashboard/"
    Then The user value "id" should be "provisioned-user"
    And The last login timestamp of "provisioned-user" should not be empty

  Scenario: Authenticating using environment variable with SSO and unsuccessful check if user exists on backend
    Given The setting "type" is set to "environment-variable"
    And The setting "general-require_provisioned_account" is set to "1"
    And The setting "general-uid_mapping" is set to "REMOTE_USER"
    And The environment variable "REMOTE_USER" is set to "certainly-not-provisioned-user"
    When I send a GET request to "http://localhost:8080/index.php/login"
    Then I should be redirected to "http://localhost:8080/index.php/apps/user_saml/saml/notProvisioned"

  Scenario: Authenticating using environment variable with SSO as a disabled user on backend
    Given A local user with uid "provisioned-disabled-user" exists
    And A local user with uid "provisioned-disabled-user" is disabled
    And The setting "type" is set to "environment-variable"
    And The setting "general-require_provisioned_account" is set to "1"
    And The setting "general-uid_mapping" is set to "REMOTE_USER"
    And The environment variable "REMOTE_USER" is set to "provisioned-disabled-user"
    When I send a GET request to "http://localhost:8080/index.php/login"
    Then I should be redirected to "http://localhost:8080/index.php/apps/user_saml/saml/error"
