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
use OCP\IURLGenerator;

class SAMLSettings {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;

	/**
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 */
	public function __construct(IURLGenerator $urlGenerator,
								IConfig $config) {
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
	}

	public function getOneLoginSettingsArray() {
		$settings = [
			'strict' => true,
			'security' => [
				'nameIdEncrypted' => ($this->config->getAppValue('user_saml', 'security-nameIdEncrypted', '0') === '1') ? true : false,
				'authnRequestsSigned' => ($this->config->getAppValue('user_saml', 'security-authnRequestsSigned', '0') === '1') ? true : false,
				'logoutRequestSigned' => ($this->config->getAppValue('user_saml', 'security-logoutRequestSigned', '0') === '1') ? true : false,
				'logoutResponseSigned' => ($this->config->getAppValue('user_saml', 'security-logoutResponseSigned', '0') === '1') ? true : false,
				'signMetadata' => ($this->config->getAppValue('user_saml', 'security-signMetadata', '0') === '1') ? true : false,
				'wantMessagesSigned' => ($this->config->getAppValue('user_saml', 'security-wantMessagesSigned', '0') === '1') ? true : false,
				'wantAssertionsSigned' => ($this->config->getAppValue('user_saml', 'security-wantAssertionsSigned', '0') === '1') ? true : false,
				'wantAssertionsEncrypted' => ($this->config->getAppValue('user_saml', 'security-wantAssertionsEncrypted', '0') === '1') ? true : false,
				'wantNameId' => ($this->config->getAppValue('user_saml', 'security-wantNameId', '0') === '1') ? true : false,
				'wantNameIdEncrypted' => ($this->config->getAppValue('user_saml', 'security-wantNameIdEncrypted', '0') === '1') ? true : false,
				'wantXMLValidation' => ($this->config->getAppValue('user_saml', 'security-wantXMLValidation', '0') === '1') ? true : false,
				'requestedAuthnContext' => false,
				'lowercaseUrlencoding' => ($this->config->getAppValue('user_saml', 'security-lowercaseUrlencoding', '0') === '1') ? true : false,
			],
			'sp' => [
				'entityId' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.getMetadata'),
				'assertionConsumerService' => [
					'url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.assertionConsumerService'),
				],
			],
			'idp' => [
				'entityId' => $this->config->getAppValue('user_saml', 'idp-entityId', ''),
				'singleSignOnService' => [
					'url' => $this->config->getAppValue('user_saml', 'idp-singleSignOnService.url', ''),
				],
			],
		];

		$spx509cert = $this->config->getAppValue('user_saml', 'sp-x509cert', '');
		$spxprivateKey = $this->config->getAppValue('user_saml', 'sp-privateKey', '');
		if($spx509cert !== '') {
			$settings['sp']['x509cert'] = $spx509cert;
		}
		if($spxprivateKey !== '') {
			$settings['sp']['privateKey'] = $spxprivateKey;
		}

		$idpx509cert = $this->config->getAppValue('user_saml', 'idp-x509cert', '');
		if($idpx509cert !== '') {
			$settings['idp']['x509cert'] = $idpx509cert;
		}

		$slo = $this->config->getAppValue('user_saml', 'idp-singleLogoutService.url', '');
		if($slo !== '') {
			$settings['idp']['singleLogoutService'] = [
				'url' => $this->config->getAppValue('user_saml', 'idp-singleLogoutService.url', ''),
			];
			$settings['sp']['singleLogoutService'] = [
				'url' => $this->urlGenerator->linkToRouteAbsolute('user_saml.SAML.singleLogoutService'),
			];
		}

		return $settings;
	}
}

