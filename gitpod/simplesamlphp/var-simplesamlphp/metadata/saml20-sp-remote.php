<?php
/**
 * SAML 2.0 remote SP metadata for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */

/*
 * Example SimpleSAMLphp SAML 2.0 SP
 */
$metadata['http://idptestbed/sp/simplesamlphp'] = array(
	'AssertionConsumerService' => 'https://idptestbed/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
	'SingleLogoutService' => 'https://idptestbed/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp',
);