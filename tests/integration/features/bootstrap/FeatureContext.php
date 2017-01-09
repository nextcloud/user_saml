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
		$this->client = new \GuzzleHttp\Client([
			'cookies' => true,
			'verify' => false,
			'allow_redirects' => [
				'referer'         => true,
				'track_redirects' => true,
			],
		]);
	}

	/** @AfterScenario */
	public function after() {
		$users = [
			'student1',
		];

		foreach($users as $user) {
			shell_exec(
				sprintf(
					'sudo -u apache /opt/rh/rh-php56/root/usr/bin/php %s user:delete %s',
					__DIR__ . '/../../../../../../occ',
					$user
				)
			);
		}

		foreach($this->changedSettings as $setting) {
			shell_exec(
				sprintf(
					'sudo -u apache /opt/rh/rh-php56/root/usr/bin/php %s config:app:delete user_saml %s',
					__DIR__ . '/../../../../../../occ',
					$setting
				)
			);
		}
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
		$this->changedSettings[] = $settingName;
		shell_exec(
			sprintf(
				'sudo -u apache /opt/rh/rh-php56/root/usr/bin/php %s config:app:set --value="%s" user_saml %s',
				__DIR__ . '/../../../../../../occ',
				$value,
				$settingName
			)
		);
	}

	/**
	 * @When I send a GET request to :url
	 */
	public function iSendAGetRequestTo($url) {
		try {
			$this->response = $this->client->request('GET', $url);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			echo $e->getResponse()->getBody();
			throw $e;
		}
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
		list($url['path'])=explode(';', $url['path']);

		foreach($paramsToCheck as $param) {
			if($targetUrl[$param] !== $url[$param]) {
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
			foreach($inputElements as $node) {
				$postData[$node->getAttribute('name')] =  $node->getAttribute('value');
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
	 * @Then I should be logged-in to Nextcloud as user :userId
	 * @throws UnexpectedValueException
	 */
	public function iShouldBeLoggedInToNextcloudAsUser($userId)  {
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
		$responseArray = json_decode(json_encode((array)$xml), true);
		if($responseArray['data']['display-name'] !== $userId) {
			throw new UnexpectedValueException(
				sprintf(
					'Expected %s as value but got %s',
					$userId,
					$responseArray['data']['display-name']
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
				'sudo -u apache OC_PASS=password /opt/rh/rh-php56/root/usr/bin/php %s user:add %s --password-from-env',
				__DIR__ . '/../../../../../../occ',
				$uid
			)
		);
	}

}
