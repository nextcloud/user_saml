<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class FeatureContext implements Context {
	/** @var \GuzzleHttp\Message\Response */
	private $response;
	/** @var \GuzzleHttp\Client */
	private $client;
	/** @var array */
	private $changedSettings = [];

	private const ENV_CONFIG_FILE = __DIR__ . '/../../../../../../config/env.config.php';
	private const MAIN_CONFIG_FILE = __DIR__ . '/../../../../../../config/config.php';
	private CookieJar $cookieJar;

	public function __construct() {
		date_default_timezone_set('Europe/Berlin');
	}

	/** @BeforeScenario */
	public function before() {
		$this->cookieJar = new CookieJar();
		$this->client = new \GuzzleHttp\Client([
			'version' => 2.0,
			'cookies' => $this->cookieJar,
			'verify' => false,
			'allow_redirects' => [
				'referer' => true,
				'track_redirects' => true,
			],
		]);
	}

	/** @AfterScenario */
	public function after() {
		$users = [
			'student1',
		];

		foreach ($users as $user) {
			shell_exec(
				sprintf(
					'%s %s user:delete %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$user
				)
			);
		}

		$groups = [
			'Astrophysics',
			'SAML_Astrophysics',
			'Students',
			'SAML_Students'
		];

		foreach ($groups as $group) {
			shell_exec(
				sprintf(
					'%s %s group:delete "%s"',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$group
				)
			);
		}

		if (file_exists(self::ENV_CONFIG_FILE)) {
			unlink(self::ENV_CONFIG_FILE);
		}

		foreach ($this->changedSettings as $setting) {
			shell_exec(
				sprintf(
					'%s %s config:app:delete user_saml %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$setting
				)
			);
		}

		shell_exec(
			sprintf(
				'%s %s saml:config:delete 1',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
			)
		);

		$this->changedSettings = [];
	}

	/**
	 * @Given The setting :settingName is set to :value
	 */
	public function theSettingIsSetTo(string $settingName, string $value): void {
		if (in_array($settingName, [
			'type',
			'general-require_provisioned_account',
			'general-allow_multiple_user_back_ends',
			'localGroupsCheckForMigration',
		])) {
			$this->changedSettings[] = $settingName;
			shell_exec(
				sprintf(
					'%s %s config:app:set --value="%s" user_saml %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$value,
					$settingName
				)
			);
			return;
		}

		shell_exec(
			sprintf(
				'%s %s saml:config:set --"%s"="%s" %d',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$settingName,
				$value,
				1
			)
		);
	}
	/**
	 * @Given The config :configName is :value
	 */
	public function theConfigIs(string $configName, string $value): void {
		shell_exec(
			sprintf(
				'%s %s saml:config:set --"%s"="%s" %d',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$configName,
				$value,
				1
			)
		);
	}
	/**
	 * @Then The config :configName should be :expectedValue
	 */
	public function theConfigShouldBe(string $configName, string $expectedValue): void {
		$json = shell_exec(
			sprintf(
				'%s %s saml:config:get --providerId %d --output json',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				1
			)
		);
		$json = json_decode($json);
		$value = $json->{'1'}->$configName;
		if ($value !== $expectedValue) {
			throw new UnexpectedValueException(
				sprintf('Config value for %s is %s, but expected was %s', $configName, $value, $expectedValue)
			);
		}
	}

	/**
	 * @Then The setting :settingName is currently :expectedValue
	 */
	public function theSettingIsCurrently(string $settingName, string $expectedValue): void {
		if (in_array($settingName, [
			'type',
			'general-require_provisioned_account',
			'general-allow_multiple_user_back_ends',
			'localGroupsCheckForMigration',
		])) {
			$this->changedSettings[] = $settingName;
			$value = shell_exec(
				sprintf(
					'%s %s config:app:get user_saml %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$settingName
				)
			);
		} else {
			$value = shell_exec(
				sprintf(
					'%s %s saml:config:set --"%s" %d',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$settingName,
					1
				)
			);
		}
		$value = rtrim($value); // remove trailing newline from shell output
		if ($value !== $expectedValue) {
			throw new UnexpectedValueException(
				sprintf('Config value for %s is %s, but expected was %s', $settingName, $value, $expectedValue)
			);
		}
	}

	/**
	 * @When I send a GET request to :url
	 */
	public function iSendAGetRequestTo($url) {
		$this->response = $this->client->request(
			'GET',
			$url,
			[
				'headers' => [
					'Accept' => 'text/html',
				],
				'query' => [
					'idp' => 1
				],
			]
		);
	}
	/**
	 * @When I send a GET request with query params to :url
	 */
	public function iSendAGetRequestWithQueryParamsTo($url) {
		$url = $url . '&idp=1';
		$query = parse_url($url)['query'];
		$url = str_replace('?' . $query, '', $url);
		$this->response = $this->client->request(
			'GET',
			$url,
			[
				'headers' => [
					'Accept' => 'text/html',
				],
				'query' => $query
			]
		);
	}

	/**
	 * @Then I should be redirected to :targetUrl
	 *
	 * @param string $targetUrl
	 * @throws InvalidArgumentException
	 */
	public function iShouldBeRedirectedTo($targetUrl) {
		$redirectHeader = $this->response->getHeader('X-Guzzle-Redirect-History');
		$lastUrl = $redirectHeader[count($redirectHeader) - 1];
		$url = parse_url((string)$lastUrl);
		$targetUrl = parse_url($targetUrl);
		$paramsToCheck = [
			'scheme',
			'host',
			'path',
		];

		// Remove everything after a comma in the URL since cookies are passed there
		[$url['path']] = explode(';', $url['path']);

		foreach ($paramsToCheck as $param) {
			if ($targetUrl[$param] !== $url[$param]) {
				throw new InvalidArgumentException(
					sprintf(
						'Expected %s for parameter %s, got %s',
						$targetUrl[$param],
						$param,
						$url[$param]
					)
				);
			}
		}
	}
	/**
	 * @Then I should be redirected to :targetUrl with query params
	 *
	 * @param string $targetUrl
	 * @throws InvalidArgumentException
	 */
	public function iShouldBeRedirectedToWithQueryParams($targetUrl) {
		$redirectHeader = $this->response->getHeader('X-Guzzle-Redirect-History');
		$firstUrl = $redirectHeader[0];
		$firstUrlParsed = parse_url((string)$firstUrl);
		$targetUrl = parse_url($targetUrl);
		$paramsToCheck = [
			'scheme',
			'host',
			'path',
			'query'
		];

		// Remove everything after a comma in the URL since cookies are passed there
		[$firstUrlParsed['path']] = explode(';', $firstUrlParsed['path']);
		$passthroughParams = $targetUrl['query'];
		foreach ($paramsToCheck as $param) {
			if ($param == 'query') {
				foreach (explode('&', $passthroughParams) as $passthrough) {
					if (!str_contains((string)$firstUrl, $passthrough)) {
						throw new InvalidArgumentException(
							sprintf(
								'Expected to find %s for parameter %s',
								$passthrough,
								$param,
							)
						);
					}
				}
			} else {
				if ($targetUrl[$param] !== $firstUrlParsed[$param]) {
					throw new InvalidArgumentException(
						sprintf(
							'Expected %s for parameter %s, got %s',
							$targetUrl[$param],
							$param,
							$firstUrlParsed[$param]
						)
					);
				}
			}
		}
	}

	/**
	 * @Then I send a POST request to :url with the following data
	 *
	 * @param string $url
	 * @param TableNode $table
	 */
	public function iSendAPostRequestToWithTheFollowingData(
		$url,
		TableNode $table,
	) {
		$postParams = $table->getColumnsHash()[0];
		$this->response = $this->client->request(
			'POST',
			$url,
			[
				'form_params' => $postParams,
			]
		);
	}

	/**
	 * @Then The response should be a SAML redirect page that gets submitted
	 */
	public function theResponseShouldBeASamlRedirectPageThatGetsSubmitted() {
		$responseBody = $this->response->getBody();
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($responseBody);
		$xpath = new DOMXpath($domDocument);
		$postData = [];
		$inputElements = $xpath->query('//input');
		if (is_object($inputElements)) {
			/** @var DOMElement $node */
			foreach ($inputElements as $node) {
				$postData[$node->getAttribute('name')] = $node->getAttribute('value');
			}
		}

		$this->response = $this->client->request(
			'POST',
			'http://localhost:8080/index.php/apps/user_saml/saml/acs',
			[
				'form_params' => $postData,
			]
		);
	}

	/**
	 * @Then The user value :key should be :value
	 *
	 * @param string $key
	 * @param string $value
	 * @throws UnexpectedValueException
	 */
	public function theUserValueShouldBe(string $key, string $value): void {
		$this->response = $this->client->request(
			'GET',
			'http://localhost:8080/ocs/v1.php/cloud/user',
			[
				'headers' => [
					'OCS-APIRequest' => 'true',
				],
				'query' => [
					'format' => 'json',
				]
			]
		);

		$responseArray = (json_decode((string)$this->response->getBody(), true))['ocs'];

		if (!isset($responseArray['data'][$key]) || count((array)$responseArray['data'][$key]) === 0) {
			if (str_contains($key, '.')) {
				// support nested arrays, specify the key seperated by "."s, e.g. quota.total
				$keys = explode('.', $key);
				if (isset($responseArray['data'][$keys[0]])) {
					$source = $responseArray['data'];
					foreach ($keys as $subKey) {
						if (isset($source[$subKey])) {
							$source = $source[$subKey];
							if (!is_array($source)) {
								$actualValue = (string)$source;
							}
						} else {
							break;
						}
					}
				}
			}

			$responseArray['data'][$key] = '';
		}

		$actualValue ??= $responseArray['data'][$key];
		if (is_array($actualValue)) {
			// transform array to string, ensuring values are in the same order
			$value = explode(',', $value);
			$value = array_map('trim', $value);
			sort($value);
			$value = implode(',', $value);

			sort($actualValue);
			$actualValue = implode(',', $actualValue);
		}

		if ($actualValue !== $value) {
			throw new UnexpectedValueException(
				sprintf(
					'Expected %s as value but got %s',
					$value,
					$actualValue
				)
			);
		}
	}

	/**
	 * @Then The group :group has exactly the members :memberList
	 */
	public function theGroupHasExactlyTheMembers(string $group, string $memberList): void {
		$this->response = $this->client->request(
			'GET',
			sprintf('http://localhost:8080/ocs/v2.php/cloud/groups/%s', $group),
			[
				'headers' => [
					'OCS-APIRequest' => 'true',
				],
				'query' => [
					'format' => 'json',
				],
				'auth' => [
					'admin',
					'admin'
				],
				'cookies' => '',
			]
		);

		$responseArray = (json_decode((string)$this->response->getBody(), true))['ocs'];
		if ($responseArray['meta']['statuscode'] !== 200) {
			throw new UnexpectedValueException(sprintf('Expected 200 status code but got %d', $responseArray['meta']['statusCode']));
		}

		$expectedMembers = array_map('trim', explode(',', $memberList));
		$actualMembers = array_map('trim', $responseArray['data']['users']);

		sort($expectedMembers);
		sort($actualMembers);

		if ($expectedMembers !== $actualMembers) {
			throw new UnexpectedValueException(sprintf('Unexpectedly the returned members are: %s', implode(', ', $actualMembers)));
		}
	}

	/**
	 * @Given A local user with uid :uid exists
	 * @param string $uid
	 */
	public function aLocalUserWithUidExists($uid) {
		shell_exec(
			sprintf(
				'OC_PASS=password %s %s user:add %s --display-name "Default displayname of ' . $uid . '" --password-from-env',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);
	}

	/**
	 * @Given A local user with uid :uid is disabled
	 * @param string $uid
	 */
	public function aLocalUserWithUidIsDisabled($uid) {
		shell_exec(
			sprintf(
				'OC_PASS=password %s %s user:disable %s',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);
	}

	/**
	 * @Then I hack :uid into existence
	 */
	public function hackUserIntoExistence(string $uid): void {
		rename(__DIR__ . '/../../../../../../data/' . $uid, __DIR__ . '/../../../../../../data/hide-' . $uid);
		shell_exec(
			sprintf(
				'OC_PASS=password %s %s user:add %s --display-name "Default displayname of ' . $uid . '" --password-from-env',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);
		rename(__DIR__ . '/../../../../../../data/hide-' . $uid, __DIR__ . '/../../../../../../data/' . $uid);
	}

	/**
	 * @Then The last login timestamp of :uid should not be empty
	 *
	 * @param string $uid
	 * @throws UnexpectedValueException
	 */
	public function theLastLoginTimestampOfShouldNotBeEmpty($uid) {
		$response = shell_exec(
			sprintf(
				'OC_PASS=password %s %s user:lastseen %s',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);

		$response = trim($response);
		$loginTimeProof = 's last login: ';
		if (!str_contains($response, $loginTimeProof)) {
			throw new UnexpectedValueException("Expected last login message, found instead '$response'");
		}
	}

	/**
	 * @Then User :userId is part of the groups :groups
	 */
	public function theUserIsPartOfTheseGroups(string $userId, string $groups) {
		$response = shell_exec(
			sprintf(
				'%s %s user:info %s --output=json',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$userId
			)
		);

		$groupsActual = json_decode(trim($response), true)['groups'];
		$groupsExpected = array_map('trim', explode(',', $groups));

		foreach ($groupsExpected as $expectedGroup) {
			if (!in_array($expectedGroup, $groupsActual)) {
				$actualGroupStr = implode(', ', $groupsActual);
				throw new UnexpectedValueException("Expected to find $expectedGroup in '$actualGroupStr'");
			}
		}
	}

	/**
	 * @Given The environment variable :key is set to :value
	 */
	public function theEnvironmentVariableIsSetTo($key, $value) {
		// It generates an extra config file that injects the value to $_SERVER
		// (as used in `SAMLController::login()`), so that it stays across
		// requests in PHPs built-in server.
		if (file_exists(self::ENV_CONFIG_FILE)) {
			$envConfigPhp = file_get_contents(self::ENV_CONFIG_FILE) . PHP_EOL;
		} else {
			$envConfigPhp = <<<EOF
<?php
EOF . PHP_EOL;
		}
		$envConfigPhp .= <<<EOF
\$_SERVER["$key"] = "$value";
EOF;
		file_put_contents(self::ENV_CONFIG_FILE, $envConfigPhp);
	}

	/**
	 * @Given /^the group "([^"]*)" should exists$/
	 */
	public function theGroupShouldExists(string $gid): void {
		$response = shell_exec(
			sprintf(
				'%s %s group:info --output=json "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$gid
			)
		);

		$responseArray = json_decode($response, true);
		if (!isset($responseArray['groupID']) || $responseArray['groupID'] !== $gid) {
			throw new UnexpectedValueException('Group does not exist');
		}
	}

	/**
	 * @When /^I execute the background job for class "([^"]*)"$/
	 */
	public function iExecuteTheBackgroundJobForClass(string $className) {
		$response = shell_exec(
			sprintf(
				'%s %s background-job:list --output=json --class %s',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$className
			)
		);

		$responseArray = json_decode($response, true);
		if (count($responseArray) === 0) {
			throw new UnexpectedValueException('Background job was not enqueued');
		}

		foreach ($responseArray as $jobDetails) {
			$jobID = (int)$jobDetails['id'];
			$response = shell_exec(
				sprintf(
					'%s %s background-job:execute --force-execute %d',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$jobID
				)
			);
		}
	}

	/**
	 * @Then /^the group backend of "([^"]*)" should be "([^"]*)"$/
	 */
	public function theGroupBackendOfShouldBe(string $groupId, string $backendName) {
		$response = shell_exec(
			sprintf(
				'%s %s group:info --output=json "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$groupId
			)
		);

		$responseArray = json_decode($response, true);
		if (!isset($responseArray['groupID']) || $responseArray['groupID'] !== $groupId) {
			throw new UnexpectedValueException('Group does not exist');
		}
		if (!in_array($backendName, $responseArray['backends'], true)) {
			throw new UnexpectedValueException('Group does not belong to this backend');
		}
	}

	/**
	 * @Given /^Then the group backend of "([^"]*)" must not be "([^"]*)"$/
	 */
	public function thenTheGroupBackendOfMustNotBe(string $groupId, string $backendName) {
		try {
			$this->theGroupBackendOfShouldBe($groupId, $backendName);
			throw new UnexpectedValueException('Group does belong to this backend');
		} catch (UnexpectedValueException $e) {
			if ($e->getMessage() !== 'Group does not belong to this backend') {
				throw $e;
			}
		}
	}

	/**
	 * @Given /^the local group "([^"]*)" is created$/
	 */
	public function theLocalGroupIsCreated(string $groupName) {
		shell_exec(
			sprintf(
				'%s %s group:add "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$groupName
			)
		);
	}

	/**
	 * @Given the group :group is deleted
	 */
	public function theGroupIsDeleted(string $group) {
		shell_exec(
			sprintf(
				'%s %s group:delete "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$group
			)
		);
	}

	/**
	 * @Given /^I send a GET request with requesttoken to "([^"]*)"$/
	 */
	public function iSendAGETRequestWithRequesttokenTo($url) {
		$requestToken = substr(
			(string)preg_replace(
				'/(.*)data-requesttoken="(.*)">(.*)/sm',
				'\2',
				(string)$this->response->getBody()->getContents()
			),
			0,
			89
		);
		$this->response = $this->client->request(
			'GET',
			$url,
			[
				'query' => [
					'requesttoken' => $requestToken
				],
			]
		);
	}

	/**
	 * @Given /^the cookies are cleared$/
	 */
	public function theCookiesAreCleared(): void {
		$this->cookieJar->clear();
	}

	/**
	 * @Given /^the user "([^"]*)" is added to the group "([^"]*)"$/
	 */
	public function theUserIsAddedToTheGroup(string $userId, string $groupId) {
		shell_exec(
			sprintf(
				'%s %s group:adduser "%s" "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$groupId,
				$userId
			)
		);
	}

	/**
	 * @Given I run the copy-incomplete-members command
	 */
	public function theCopyIncompleteMembersCommandIsRun() {
		$out = shell_exec(
			sprintf(
				'%s %s saml:group-migration:copy-incomplete-members --verbose',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
			)
		);
		if ($out === false || $out === null) {
			throw new RuntimeException('Failed to execute saml:group-migration:copy-incomplete-members command');
		}
	}

	/**
	 * @Given I :stateAction the app :appId
	 */
	public function theAppIsEnabledOrDisabled(string $appId, string $stateAction) {
		shell_exec(
			sprintf(
				'%s %s app:%s "%s"',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$stateAction,
				$appId
			)
		);
	}

	/**
	 * @Given /^I expect no background job for class "([^"]*)"$/
	 */
	public function iExpectNoBackgroundJobForClassOCAUser_SAMLJobsMigrateGroups(string $className) {
		$response = shell_exec(
			sprintf(
				'%s %s background-job:list --output=json --class %s',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$className
			)
		);

		$responseArray = json_decode($response, true);
		if (count($responseArray) > 0) {
			throw new UnexpectedValueException('Background job axctuaslly was enqueued!');
		}
	}

	/**
	 * @Then The form method should be POST
	 */
	public function theFormMethodShouldBePost() {
		$responseBody = (string)$this->response->getBody();
		$domDocument = new DOMDocument();
		@$domDocument->loadHTML($responseBody);
		$xpath = new DOMXpath($domDocument);
		$formElements = $xpath->query("//form[@method='post' or @method='POST']");
		if ($formElements->length === 0) {
			throw new \Exception("Expected form method 'POST' not found in response");
		}
	}

	/**
	 * @Then The response should contain the form with action :action
	 */
	public function theResponseShouldContainTheFormWithAction($action) {
		$responseBody = (string)$this->response->getBody();
		if (!str_contains($responseBody, 'action="' . $action . '"')) {
			throw new \Exception("Expected form action '$action' not found in response");
		}
	}

	/**
	 * @Then The form should contain input fields :fields
	 */
	public function theFormShouldContainInputFields($fields) {
		$responseBody = (string)$this->response->getBody();
		$domDocument = new DOMDocument();
		@$domDocument->loadHTML($responseBody);
		$xpath = new DOMXpath($domDocument);
		$fieldsArray = explode(',', (string)$fields);
		foreach ($fieldsArray as $field) {
			$inputElements = $xpath->query("//input[@name='" . trim($field) . "']");
			if ($inputElements->length === 0) {
				throw new \Exception("Expected input field '$field' not found in response");
			}
		}
	}

	/**
	 * @When I submit the SAML form
	 */
	public function iSubmitTheSAMLForm() {
		$responseBody = (string)$this->response->getBody();
		$domDocument = new DOMDocument();
		@$domDocument->loadHTML($responseBody);
		$xpath = new DOMXpath($domDocument);

		// Find the form action
		$formAction = $xpath->query('//form')->item(0)->getAttribute('action');

		// Get the specified hidden input fields
		$fields = ['SAMLRequest', 'RelayState', 'SigAlg', 'Signature'];
		$postData = [];
		foreach ($fields as $field) {
			$inputElement = $xpath->query("//input[@type='hidden' and @name='" . $field . "']");
			if ($inputElement->length === 0) {
				throw new \Exception("Expected hidden input field '$field' not found in response");
			}
			$postData[$field] = $inputElement->item(0)->getAttribute('value');
		}

		// Send the POST request with the hidden input data
		try {
			$this->response = $this->client->request(
				'POST',
				$formAction,
				[
					'form_params' => $postData,
					'headers' => [
						'Content-Type' => 'application/x-www-form-urlencoded',
						'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
					],
					'cookies' => $this->cookieJar,
				]
			);
		} catch (RequestException $e) {
			echo 'RequestException: ' . $e->getMessage() . "\n";
			if ($e->hasResponse()) {
				$response = $e->getResponse();
				echo 'Status Code: ' . $response->getStatusCode() . "\n";
				echo 'Headers: ' . json_encode($response->getHeaders()) . "\n";
				echo 'Body: ' . $response->getBody() . "\n";
				echo 'Request Headers: ' . json_encode($e->getRequest()->getHeaders()) . "\n";
				echo 'Request Body: ' . $e->getRequest()->getBody() . "\n";
			}
		} catch (GuzzleException $e) {
			echo 'GuzzleException: ' . $e->getMessage() . "\n";
		}
	}
}
