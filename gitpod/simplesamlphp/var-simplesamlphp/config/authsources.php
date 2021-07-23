<?php

$config = array(

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

    'ldap' => array(
        'ldap:LDAP',

        'hostname' => 'ldap',

        'enable_tls' => FALSE,

        'debug' => TRUE,

        'timeout' => 0,

        'port' => 389,

        'referrals' => TRUE,

        'attributes' => null,

        'dnpattern' => 'uid=%username%,ou=People,dc=user_saml_gitpod',

        'search.enable' => true,

        'search.base' => 'ou=People,dc=user_saml_gitpod',

        'search.attributes' => ['uid', 'mail'],

        'search.username' => NULL,
        'search.password' => NULL,

        'priv.read' => FALSE,
        
        'priv.username' => NULL,
        'priv.password' => NULL,

    ),

);
