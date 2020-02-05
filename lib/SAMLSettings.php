<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OneLogin\Saml2\Constants;

class SAMLSettings {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;
	/** @var IRequest */
	private $request;
	/** @var ISession */
	private $session;
	/** @var array list of global settings which are valid for every idp */
	private $globalSettings = ['general-require_provisioned_account', 'general-allow_multiple_user_back_ends', 'general-use_saml_auth_for_desktop'];

	/**
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param ISession $session
	 */
	public function __construct(IURLGenerator $urlGenerator,
								IConfig $config,
								IRequest $request,
								ISession $session) {
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->request = $request;
		$this->session = $session;
	}

	/**
	 * get list of the configured IDPs
	 *
	 * @return array
	 */
	public function getListOfIdps() {
		$result = [];

		$providerIds = explode(',', $this->config->getAppValue('user_saml', 'providerIds', '1'));
		natsort($providerIds);

		foreach ($providerIds as $id) {
			$prefix = $id === '1' ? '' : $id .'-';
			$result[$id] = $this->config->getAppValue('user_saml', $prefix . 'general-idp0_display_name', '');
		}

		asort($result);

		return $result;
	}

	/**
	 * check if multiple user back ends are allowed
	 *
	 * @return bool
	 */
	public function allowMultipleUserBackEnds() {
		$type = $this->config->getAppValue('user_saml', 'type');
		$setting = $this->config->getAppValue('user_saml', 'general-allow_multiple_user_back_ends', '0');
		return  ($setting === '1' && $type === 'saml');
	}

	/**
	 * get config for given IDP
	 *
	 * @param int $idp
	 * @return array
	 */
	public function getOneLoginSettingsArray($idp) {

		$prefix = '';
		if ($idp > 1) {
			$prefix = $idp . '-';
		}

		$settings = [
			'strict' => true,
			'debug' => $this->config->getSystemValue('debug', false),
			'baseurl' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.base'),
			'security' => [
				'nameIdEncrypted' => ($this->config->getAppValue('user_saml', $prefix . 'security-nameIdEncrypted', '0') === '1') ? true : false,
				'authnRequestsSigned' => ($this->config->getAppValue('user_saml', $prefix . 'security-authnRequestsSigned', '0') === '1') ? true : false,
				'logoutRequestSigned' => ($this->config->getAppValue('user_saml', $prefix . 'security-logoutRequestSigned', '0') === '1') ? true : false,
				'logoutResponseSigned' => ($this->config->getAppValue('user_saml', $prefix . 'security-logoutResponseSigned', '0') === '1') ? true : false,
				'signMetadata' => ($this->config->getAppValue('user_saml', $prefix . 'security-signMetadata', '0') === '1') ? true : false,
				'wantMessagesSigned' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantMessagesSigned', '0') === '1') ? true : false,
				'wantAssertionsSigned' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantAssertionsSigned', '0') === '1') ? true : false,
				'wantAssertionsEncrypted' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantAssertionsEncrypted', '0') === '1') ? true : false,
				'wantNameId' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantNameId', '0') === '1') ? true : false,
				'wantNameIdEncrypted' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantNameIdEncrypted', '0') === '1') ? true : false,
				'wantXMLValidation' => ($this->config->getAppValue('user_saml', $prefix . 'security-wantXMLValidation', '0') === '1') ? true : false,
				'requestedAuthnContext' => false,
				'lowercaseUrlencoding' => ($this->config->getAppValue('user_saml', $prefix . 'security-lowercaseUrlencoding', '0') === '1') ? true : false,
				'signatureAlgorithm' => $this->config->getAppValue('user_saml', $prefix . 'security-signatureAlgorithm', null)
			],
			'sp' => [
				'entityId' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.getMetadata'),
				'assertionConsumerService' => [
					'url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.assertionConsumerService'),
				],
				'NameIDFormat' => $this->config->getAppValue('user_saml', $prefix . 'sp-name-id-format', Constants::NAMEID_UNSPECIFIED)
			],
			'idp' => [
				'entityId' => $this->config->getAppValue('user_saml', $prefix . 'idp-entityId', ''),
				'singleSignOnService' => [
					'url' => $this->config->getAppValue('user_saml', $prefix . 'idp-singleSignOnService.url', ''),
				],
			],
		];

		$spx509cert = $this->config->getAppValue('user_saml', $prefix . 'sp-x509cert', '');
		$spxprivateKey = $this->config->getAppValue('user_saml', $prefix . 'sp-privateKey', '');
		if($spx509cert !== '') {
			$settings['sp']['x509cert'] = $spx509cert;
		}
		if($spxprivateKey !== '') {
			$settings['sp']['privateKey'] = $spxprivateKey;
		}

		$idpx509cert = $this->config->getAppValue('user_saml', $prefix . 'idp-x509cert', '');
		if($idpx509cert !== '') {
			$settings['idp']['x509cert'] = $idpx509cert;
		}

		$slo = $this->config->getAppValue('user_saml', $prefix . 'idp-singleLogoutService.url', '');
		if($slo !== '') {
			$settings['idp']['singleLogoutService'] = [
				'url' => $this->config->getAppValue('user_saml', $prefix . 'idp-singleLogoutService.url', ''),
			];
			$settings['sp']['singleLogoutService'] = [
				'url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.singleLogoutService'),
			];
		}

		return $settings;
	}

	/**
	 * calculate prefix for config values
	 *
	 * @param string name of the setting
	 * @return string
	 */
	public function getPrefix($setting = '') {

		$prefix = '';
		if (!empty($setting) && in_array($setting, $this->globalSettings)) {
			return $prefix;
		}

		$idp = $this->session->get('user_saml.Idp');
		if ((int)$idp > 1) {
			$prefix = $idp . '-';
		}

		return $prefix;
	}

}
