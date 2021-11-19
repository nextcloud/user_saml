<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

class FeatureContext implements Context {
	/** @var \GuzzleHttp\Message\Response */
	private $response;
	/** @var \GuzzleHttp\Client */
	private $client;
	/** @var array */
	private $changedSettings = [];

	public function __construct() {
		date_default_timezone_set('Europe/Berlin');
	}

	/** @BeforeScenario */
	public function before() {
		$jar = new \GuzzleHttp\Cookie\FileCookieJar('/tmp/cookies_' . md5(openssl_random_pseudo_bytes(12)));
		$this->client = new \GuzzleHttp\Client([
			'cookies' => $jar,
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
					'sudo -u apache %s %s user:delete %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$user
				)
			);
		}

		foreach ($this->changedSettings as $setting) {
			shell_exec(
				sprintf(
					'sudo -u apache %s %s config:app:delete user_saml %s',
					PHP_BINARY,
					__DIR__ . '/../../../../../../occ',
					$setting
				)
			);
		}

		shell_exec(
			sprintf(
				'sudo -u apache %s %s saml:config:delete 1',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
			)
		);

		$this->changedSettings = [];
	}

	/**
	 * @Given The setting :settingName is set to :value
	 *
	 * @param string $settingName
	 * @param string $value
	 */
	public function theSettingIsSetTo($settingName,
									  $value) {

		if (in_array($settingName, [
			'type',
			'general-require_provisioned_account',
			'general-allow_multiple_user_back_ends',
			'general-use_saml_auth_for_desktop'
		])) {
			$this->changedSettings[] = $settingName;
			shell_exec(
				sprintf(
					'sudo -u apache %s %s config:app:set --value="%s" user_saml %s',
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
				'sudo -u apache %s %s saml:config:set --"%s"="%s" %d',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$settingName,
				$value,
				1
			)
		);
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
	 * @Then I should be redirected to :targetUrl
	 *
	 * @param string $targetUrl
	 * @throws InvalidArgumentException
	 */
	public function iShouldBeRedirectedTo($targetUrl) {
		$redirectHeader = $this->response->getHeader('X-Guzzle-Redirect-History');
		$lastUrl = $redirectHeader[count($redirectHeader) - 1];
		$url = parse_url($lastUrl);
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
	 * @Then I send a POST request to :url with the following data
	 *
	 * @param string $url
	 * @param TableNode $table
	 */
	public function iSendAPostRequestToWithTheFollowingData($url,
															TableNode $table) {
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
			'http://localhost/index.php/apps/user_saml/saml/acs',
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
	public function thUserValueShouldBe($key, $value) {
		$this->response = $this->client->request(
			'GET',
			'http://localhost/ocs/v1.php/cloud/user',
			[
				'headers' => [
					'OCS-APIRequest' => 'true',
				],
			]
		);

		$xml = simplexml_load_string($this->response->getBody());
		/** @var array $responseArray */
		$responseArray = json_decode(json_encode((array)$xml), true);

		if (count((array)$responseArray['data'][$key]) === 0) {
			$responseArray['data'][$key] = '';
		}
		$actualValue = $responseArray['data'][$key];

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
	 * @Given A local user with uid :uid exists
	 * @param string $uid
	 */
	public function aLocalUserWithUidExists($uid) {
		shell_exec(
			sprintf(
				'sudo -u apache OC_PASS=password %s %s user:add %s --display-name "Default displayname of '.$uid.'" --password-from-env',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);
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
				'sudo -u apache OC_PASS=password %s %s user:lastseen %s',
				PHP_BINARY,
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);

		$response = trim($response);
		$expectedStringStart = "$uid`s last login: ";
		if (substr($response, 0, strlen($expectedStringStart)) !== $expectedStringStart) {
			throw new UnexpectedValueException("Expected last login message, found instead '$response'");
		}
	}

	/**
	 * @Given The environment variable :key is set to :value
	 */
	public function theEnvironmentVariableIsSetTo($key, $value) {
		file_put_contents(__DIR__ . '/../../../../../../.htaccess', "\nSetEnv $key $value\n", FILE_APPEND);
	}
}
