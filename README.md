# Nextcloud SSO & SAML Authentication
:lock: App for authenticating Nextcloud users using SAML

Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)

The following providers are supported and tested at the moment:

**SAML 2.0**

- OneLogin
- Shibboleth
- Active Directory Federation Services (ADFS)
- Authentication via Environment Variable

**Authentication via Environment Variable**
- Kerberos (mod_auth_kerb)
- CAS
- Any other provider that authenticates using the environment variable

While theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are **not** part of any internal test matrix.
