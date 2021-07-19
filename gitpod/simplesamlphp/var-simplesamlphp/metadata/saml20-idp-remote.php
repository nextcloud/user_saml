<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['https://idptestbed/idp/simplesamlphp'] = array(
	'name' => array(
		'en' => 'SimpleSAMLphp IdP',
	),
	'description'          => 'Test SimpleSAMLphp IdP.',

	'SingleSignOnService'  => 'https://idptestbed/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://idptestbed/simplesaml/saml2/idp/SingleLogoutService.php',
	'certificate' => 'server.crt',
);