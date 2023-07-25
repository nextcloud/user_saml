OC.L10N.register(
    "user_saml",
    {
    "This user account is disabled, please contact your administrator." : "Aquest compte d'usuari està inhabilitat, contacteu amb l'administrador.",
    "Saved" : "Desat",
    "Could not save" : "No s'ha pogut desar",
    "Provider" : "Proveïdor",
    "Unknown error, please check the log file for more details." : "Error desconegut, comprovar el fitxer de registre per a més detalls.",
    "Direct log in" : "Entrada directa",
    "SSO & SAML log in" : "Entrada SSO i SAML",
    "This page should not be visited directly." : "No s' hauria de visitar directament aquesta plana.",
    "Provider " : "Proveïdor ",
    "X.509 certificate of the Service Provider" : "X.509 El certificat d'aquest servidor és invàlid",
    "Private key of the Service Provider" : "Clau privada del proveïdor de serveis",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "Indica que la identificació del nom de la <samlp: logout Request> enviada per aquest SP serà xifrada.",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "Indica si es signaran els missatges <samlp: AuthnRequest> enviats per aquest SP. [Les metadades del SP oferiran aquesta informació]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "Indica si els signes <samlp: logoutRequest> enviats per aquest SP seran signats.",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "Indica si es signaran els missatges <samlp: logoutResponse> enviats per aquest SP.",
    "Whether the metadata should be signed." : "S’han de signar les metadades.",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "Indica un requisit per a la signatura de la <samlp: Response>, <samlp: Output Request> i <samlp: Output Response> elements rebuts per aquest SP.",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "Indica un requisit per a la signatura dels elements <saml: Assertion> rebuts per aquest SP. [Les metadades del SP oferiran aquesta informació]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "Indica un requisit perquè els elements <saml: Assertion> rebuts per aquest SP es codifiquin.",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : " Indica un requisit per a l'element NameID de la resposta SAML rebuda per aquest SP per estar present.",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "Indica un requisit perquè l'encriptació de nom d'aquest SP sigui xifrada.",
    "Indicates if the SP will validate all received XML." : "Indica si el SP validarà tots els XML rebuts.",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "ADFS URL: codifica les dades SAML com a minúscules, i el kit d'eines per defecte usa majúscules. Activa la compatibilitat ADFS sobre la verificació de la signatura.",
    "Algorithm that the toolkit will use on signing process." : "Algorisme que s'emprarà en el procés de signatura.",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "Recupereu els paràmetres de consulta de $_SERVER. Alguns servidors SAML ho requereixen a les sol·licituds SLO.",
    "Attribute to map the UID to." : "Atribut per assignar un UID a.",
    "Only allow authentication if an account exists on some other backend (e.g. LDAP)." : "Només permet l'autenticació si existeix un compte en un altre rerefons (p. ex., LDAP).",
    "Attribute to map the displayname to." : "Atribut per assignar el nom de la pantalla a.",
    "Attribute to map the email address to." : "Atribut per assignar l'adreça de correu electrònic a.",
    "Attribute to map the quota to." : "Atribut per mapejar la quota.",
    "Attribute to map the users groups to." : "Atribut per mapejar els grups d'usuaris.",
    "Attribute to map the users home to." : "Atribut per ubicar al mapa la casa dels usuaris.",
    "Attribute to map the users MFA login status" : "Atribut per assignar l'estat d'inici de sessió MFA dels usuaris",
    "Reject members of these groups. This setting has precedence over required memberships." : "Rebutja els membres d'aquests grups. Aquesta configuració té prioritat sobre els membres obligatoris.",
    "Group A, Group B, …" : "Grup A, Grup B, …",
    "Require membership in these groups, if any." : "Requereix la pertinença a aquests grups, si n'hi ha.",
    "Email address" : "Adreça de correu electrònic",
    "Encrypted" : "Xifrat",
    "Entity" : "Entitat",
    "Kerberos" : "Kerberos",
    "Persistent" : "Persistent",
    "Transient" : "Transitori",
    "Unspecified" : "No especificat",
    "Windows domain qualified name" : "Nom qualificat del domini de Windows (FQDN)",
    "X509 subject name" : "Nom X509 del subjecte",
    "Use SAML auth for the %s desktop clients (requires user re-authentication)" : "Utilitzeu l'autenticació SAML per als clients %s (requereix una autenticació de l'usuari)",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "Nom de visualització opcional del proveïdor d'identitat (per defecte: “Entrada SSO i SAML\")",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "Permetre l'ús de múltiples bases d'usuaris (p. ex. LDAP)",
    "SSO & SAML authentication" : "Autenticació SSO & SAML",
    "Authenticate using single sign-on" : "Autenticar mitjançant inici únic de sessió",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "Utilitzant l’app d'SSO i SAML del teu Nextcloud pots fer fàcilment possible integrar la seva solució existent d’inici únic de sessió amb Nextcloud. A més, pots utilitzar el proveïdor d'usuaris LDAP de Nextcloud per la comoditat dels usuaris. (p. ex. en compartir)\nEls proveïdors següents es dóna suport i provats actualment:\n\n * **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation serveis (ADFS)\n\n* **Autenticació mitjançant variable d’entorn**\n\t* Kerberos (mod_auth_kerb)\n\t* Qualsevol altre proveïdor que autentiqui amb una variable d'entorn\n\nMentre que teòricament qualsevol altre proveïdor d'autenticació que implementi alguna d'aquestes normes és compatible, voldríem comentar que no formen part de cap banc de proves intern.",
    "Open documentation" : "Obre la documentació",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "Assegureu-vos de configurar un usuari administratiu que pugui accedir a la instància mitjançant SSO. L'inici de sessió amb el vostre compte habitual de %s ja no serà possible, tret que hàgiu habilitat \"%s\" o aneu directament a l'URL %s.",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you go directly to the URL %s." : "Assegureu-vos de configurar un usuari administratiu que pugui accedir a la instància mitjançant SSO. L'inici de sessió amb el vostre compte habitual de %s ja no serà possible, tret que aneu directament a l'URL %s.",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Trieu si voleu autenticar amb el proveïdor de SAML incorporat a Nextcloud o si voleu autenticar-se amb una variable d'entorn.",
    "Use built-in SAML authentication" : "Utilitzeu l'autenticació SAML integrada",
    "Use environment variable" : "Utilitza la variable d'entorn",
    "Global settings" : "Paràmetres globals",
    "Remove identity provider" : "Treure proveïdor d'identitat",
    "Add identity provider" : "Afegir proveïdor d'identitat",
    "General" : "General",
    "Service Provider Data" : "Dades del proveïdor de serveis",
    "If your Service Provider should use certificates you can optionally specify them here." : "Si el vostre proveïdor de serveis ha d'utilitzar certificats, podeu especificar-los aquí.",
    "Show Service Provider settings…" : "Dades del proveïdor de serveis…",
    "Name ID format" : "Format del nom ID",
    "Identity Provider Data" : "Dades del proveïdor de serveis",
    "Configure your IdP settings here." : "Configureu aquí la configuració d'IdP.",
    "Identifier of the IdP entity (must be a URI)" : "Identificador de l'entitat IdP (ha de ser un URI)",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "Orientació URL de l'IdP on SP enviarà el missatge de sol·licitud d'autenticació",
    "Show optional Identity Provider settings…" : "Dades del proveïdor de serveis…",
    "URL Location of the IdP where the SP will send the SLO Request" : "Ubicació URL de l'IdP on SP enviarà la sol·licitud SLO",
    "URL Location of the IDP's SLO Response" : "URL Ubicació de la resposta SLO de l'IDP",
    "Public X.509 certificate of the IdP" : "Certificat públic X.509 de l'IdP",
    "Attribute mapping" : "Mapatge d’atributs",
    "If you want to optionally map attributes to the user you can configure these here." : "Si voleu assignar atributs a l'usuari opcionalment, podeu configurar-los aquí.",
    "Show attribute mapping settings…" : "Mostra la configuració del mapa d'atributs…",
    "Security settings" : "Paràmetres de seguretat",
    "For increased security we recommend enabling the following settings if supported by your environment." : "Per a una major seguretat, us recomanem que activeu la configuració següent si l'accepta el vostre entorn.",
    "Show security settings…" : "Mostra els paràmetres de seguretat…",
    "Signatures and encryption offered" : "Firmes i encriptació oferts",
    "Signatures and encryption required" : "S'han de signar i xifrar",
    "User filtering" : "Filtrat d'usuaris",
    "If you want to optionally restrict user login depending on user data, configure it here." : "Si voleu restringir opcionalment l'inici de sessió de l'usuari en funció de les dades de l'usuari, configureu-lo aquí.",
    "Show user filtering settings …" : "Mostra la configuració de filtratge d'usuaris …",
    "Download metadata XML" : "Descarrega metadades XML",
    "Reset settings" : "Reinicialitza els paràmetres",
    "Metadata invalid" : "Les metadades no són vàlides",
    "Metadata valid" : "Les metadades vàlides",
    "Error" : "Error",
    "Access denied." : "Accés denegat.",
    "Your account is denied, access to this service is thus not possible." : "El vostre compte està denegat, per tant l'accés a aquest servei no és possible.",
    "Account not provisioned." : "Compte no subministrat.",
    "Your account is not provisioned, access to this service is thus not possible." : "El vostre compte no està proveït, per tant, l'accés a aquest servei no és possible.",
    "Login options:" : "Opcions d'inici de sessió:",
    "Choose a authentication provider" : "Tria un proveïdor d'autenticació"
},
"nplurals=2; plural=(n != 1);");
