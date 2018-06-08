<?php

/**
 * @copyright Copyright (c) 2018 Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
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
namespace OCA\User_SAML\Strategies\Authentication;

use OCA\User_SAML\Exceptions\NoUserFoundException;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\ILogger;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserBackend;

/**
 * @link  http://iti.gov.br/images/repositorio/legislacao/documentos-principais/DOC-ICP-04.01_-_versao_3.2_ATRIBUICAO_DE_OID_NA_ICP-BRASIL.pdf
 */
abstract class AbstractPKIStrategy implements StrategyInterface {
	/**
	 * OID for required attributes
	 * @var string
	 */
	protected $OIDRequiredAttribute;
	/**
	 * name of uid attribute
	 * @var string
	 */
	protected $certUidAttribute;

	/**
	 * (non-PHPdoc)
	 * 
	 * @see \OCA\User_SAML\Strategies\Authentication\StrategyInterface::login()
	 */
	public function login(IConfig $config, IURLGenerator $urlGenerator, ILogger $logger, IUserManager $userManager, IUserBackend $userBackend, ISession $session) {
		$ssoUrl = $urlGenerator->getAbsoluteURL ( '/' );
		$uidMapping = $config->getAppValue ( 'user_saml', 'general-uid_mapping', 'SSL_CLIENT_CERT');
		
		// receives certificate
		$cert = "";
		if (is_array ( $_SERVER [$uidMapping] )) {
			$cert = $_SERVER [$uidMapping] [0];
		} else {
			$cert = $_SERVER [$uidMapping];
		}
		if (empty ( $cert )) {
			$logger->error ( 'Cert "' . $cert . '" is not a valid certificate', [ 
				'app' => $this->appName 
			] );
			throw new \InvalidArgumentException ( 'No valid cert given, please check your configuration' . $cert );
		}
		try {
			// extracts attribute from certificate mapping
			$PKIData = $this->parsePKIData ( $cert );
			$certUid = $PKIData [$this->OIDRequiredAttribute] [$this->certUidAttribute];
			
			// provide user from a search. Certificate attribute must exist in user search filters
			$users = $userManager->search ( $certUid );
			
			// recover nextcloud name of user
			$listKeys = array_keys ( $users );
			$nextcloud_uid = '';
			if (isset ( $listKeys [0] )) {
				$nextcloud_uid = $listKeys [0];
			}

			if (!isset ( $nextcloud_uid )) {
				throw new NoUserFoundException ('Nextcloud uid not found');
			}
			// create user session
			$_SERVER [$uidMapping] = $nextcloud_uid;
			$session->set ( 'user_saml.samlUserData', $_SERVER );
			$user = $userManager->get ( $userBackend->getCurrentUserId () );
			if (! ($user instanceof IUser)) {
				throw new NoUserFoundException();
			}
			$user->updateLastLoginTimestamp ();
		} catch ( NoUserFoundException $e ) {
			$ssoUrl = $urlGenerator->linkToRouteAbsolute ( 'user_saml.SAML.notProvisioned' );
		}
		
		return new Http\RedirectResponse ( $ssoUrl );
	}

	/**
	 * @param string $certificate
	 * @return boolean|Ambigous <multitype:, string>
	 */
	abstract protected function parsePKIData($certificate);

	/**
	 * @param string $oid
	 * @return string
	 */
	protected function oid2Hex($oid) {
		$abBinary = array ();
		$parts = explode ( '.', $oid );
		$n = 0;
		$b = 0;
		
		for($n = 0; $n < count ( $parts ); $n ++) {
			if ($n == 0) {
				$b = 40 * $parts [$n];
			} elseif ($n == 1) {
				$b += $parts [$n];
				$abBinary [] = $b;
			} else {
				$abBinary = $this->xBase128 ( $abBinary, $parts [$n], 1 );
			}
		}
		
		$value = chr ( 0x06 ) . chr ( count ( $abBinary ) );
		foreach ( $abBinary as $item ) {
			$value .= chr ( $item );
		}
		
		return $value;
	}

	/**
	 * 
	 * @param array $ab
	 * @param integer $q
	 * @param boolean $flag
	 * @return Ambigous <boolean, unknown>
	 */
	protected function xBase128(array $ab, $q, $flag) {
		$abc = $ab;
		if ($q > 127) {
			$abc = $this->xBase128 ( $abc, floor ( $q / 128 ), 0 );
		}
		
		$q = $q % 128;
		if ($flag) {
			$abc [] = $q;
		} else {
			$abc [] = 0x80 | $q;
		}
		
		return $abc;
	}

	/** 
	 * @param string $pemCertificate
	 * @return string
	 */
	protected function pem2Der($pemCertificate) {
		$aux = explode ( chr ( 0x0A ), $pemCertificate );
		$derCertificate = '';
		foreach ( $aux as $i ) {
			if ($i != '') {
				if (substr ( $i, 0, 5 ) !== '-----') {
					$derCertificate .= $i;
				}
			}
		}
		
		return base64_decode ( $derCertificate );
	}
}