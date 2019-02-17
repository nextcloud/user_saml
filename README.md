## Config

in `config.php`:

```php

user_oidc => array(
    auth_url => 'some.example.tld',
    signout_url => 'some.example.tld/signout',
    scopes => 'openid,webid',
    map_uid => 'uid',
    map_display_name => 'given_name',
    map_email => 'email',
    map_quota => '',
    map_group => '',
    map_home => '',
)

```
