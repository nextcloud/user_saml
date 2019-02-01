## Config

```config.php

oidc_backend => array(
    auth_url => 'some.example.tld',
    signout_url => 'some.example.tld/signout',
    scopes => 'openid,webid',
    uid_mapping => 'uid',
    displayName_mapping => 'given_name',
    email_mapping => 'email',
    quota_mapping => '',
    group_mapping => '',
    home_mapping => '',
)

```

## TODO

Vendor jumbojett/openid-connect-php
Change Tests