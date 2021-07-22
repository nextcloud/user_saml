<?php
/**
 * SAML 2.0 IdP configuration for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted
 */

$metadata['__DYNAMIC:1__'] = array(
	/*
	 * The hostname of the server (VHOST) that will use this SAML entity.
	 *
	 * Can be '__DEFAULT__', to use this entry by default.
	 */
	'host' => '__DEFAULT__',

	// X.509 key and certificate. Relative to the cert directory.
	'privatekey' => 'simplesamlserver.pem',
	'certificate' => 'simplesamlserver.crt',

	'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',

	'authproc' => [
		// Convert LDAP names to oids.
		100 => ['class' => 'core:AttributeMap', 'name2oid'],
	],

	/*
	 * Authentication source to use. Must be one that is configured in
	 * 'config/authsources.php'.
	 */
	'auth' => 'ldap',
);
