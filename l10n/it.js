OC.L10N.register(
    "user_saml",
    {
    "This user account is disabled, please contact your administrator." : "Questo account utente è disabilitato, contatta il tuo amministratore.",
    "Saved" : "Salvato",
    "Provider" : "Fornitore",
    "Unknown error, please check the log file for more details." : "Errore sconosciuto, controlla il file di log per ulteriori dettagli.",
    "Direct log in" : "Accesso diretto",
    "SSO & SAML log in" : "Accesso SSO e SAML",
    "This page should not be visited directly." : "Questa pagina non dovrebbe essere visitata direttamente.",
    "Provider " : "Fornitore",
    "X.509 certificate of the Service Provider" : "Certificato X.509 del fornitore di servizi",
    "Private key of the Service Provider" : "Chiave privata del fornitore di servizi",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "Indica che il nameID della <samlp:logoutRequest> inviata da questo SP sarà cifrato.",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "Indica se i messaggi <samlp:AuthnRequest> inviati da questo SP saranno firmati. [I metadati del SP forniranno queste informazioni]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "Indica se i messaggi <samlp:logoutRequest> inviati da questo SP saranno firmati.",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "Indica se i messaggi <samlp:logoutResponse> inviati da questo SP saranno firmati.",
    "Whether the metadata should be signed." : "Decidi se firmare o meno i metadati.",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "Indica la firma come requisiti per gli elementi <samlp:Response>, <samlp:LogoutRequest> e <samlp:LogoutResponse> ricevuti da questo SP.",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "Indica la firma come requisito per gli elementi <saml:Assertion> ricevuti da questo SP. [I metadati dello SP forniranno queste informazioni]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "Indica la cifratura come requisito per gli elementi <saml:Assertion> ricevuti da questo SP.",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : "Indica la presenza come requisito dell'elemento NameID nella SAMLResponse ricevuta da questo SP.",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "Indica la cifratura come requisito per il NameID ricevuto da questo SP.",
    "Indicates if the SP will validate all received XML." : "Indica se lo SP convaliderà tutti gli XML ricevuti.",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "URL ADFS-Codifica i dati SAML come lettere minuscole, mentre il sistema utilizza, in modo predefinito, le lettere maiuscole. Abilitata per compatibilità con ADFS sulla verifica della firma.",
    "Algorithm that the toolkit will use on signing process." : "Algoritmo che il toolkit utilizzerà per il processo di firma.",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "Recupera i parametri della query da $_SERVER. Alcuni server SAML lo richiedono per le richieste SLO.",
    "Attribute to map the UID to." : "Attributo a cui associare l'UID.",
    "Attribute to map the displayname to." : "Attributo a cui associare il nome visualizzato.",
    "Attribute to map the email address to." : "Attributo a cui associare l'indirizzo di posta elettronica.",
    "Attribute to map the quota to." : "Attributo a cui associare la quota.",
    "Attribute to map the users groups to." : "Attributo per associare i gruppi di utenti",
    "Attribute to map the users home to." : ".Attributo per associare le home degli utenti.",
    "Email address" : "Indirizzo email",
    "Encrypted" : "Cifrato",
    "Entity" : "Entità",
    "Kerberos" : "Kerberos",
    "Persistent" : "Persistente",
    "Transient" : "Transitorio",
    "Unspecified" : "Non specificato",
    "Windows domain qualified name" : "Nome di dominio Windows qualificato",
    "X509 subject name" : "Nome oggetto X509",
    "Use SAML auth for the %s desktop clients (requires user re-authentication)" : "Utilizza autenticazione SAML per i client desktop di %s (richiede una nuova autenticazione degli utenti)",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "Nome visualizzato facoltativo del fornitore d'identità  (predefinito: \"Accesso SSO e SAML\")",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "Consenti l'utilizzo di più motori utente (ad es. LDAP)",
    "SSO & SAML authentication" : "Autenticazione SSO e SAML",
    "Authenticate using single sign-on" : "Autenticazione con single sign-on",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "Utilizzando l'applicazione SSO e SAML di Nextcloud, puoi rendere possibile l'integrazione della tua soluzione Single-Sign-On esistente con Nextcloud. In aggiunta, puoi utilizzare il fornitore di utenti LDAP di Nextcloud per mantenere la convenienza degli utenti. (ad es. quando si condivide)\nI seguenti fornitori sono supportati e verificati al momento:\n* ** SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS) \n\n* **Autenticazione tramite variabile d'ambiente**\n\t* Kerberos (mod_auth_kerb)\n\t* Qualsiasi altro fornitore che autentichi utilizzando una variabile d'ambiente\n\nSebbene teoricamente qualsiasi altro fornitore di autenticazione che implementi uno di questi standard sia compatibile, segnaliamo che essi non sono parte della matrice dei test interni.",
    "Open documentation" : "Apri la documentazione",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won't be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "Assicurati di configurare un utente amministrativo che possa accedere all'istanza tramite SSO. L'accesso con il tuo account normale %s non sarà più possibile a meno che tu abbia abilitato \"%s\" o che tu vada direttamente all'URL %s.",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won't be possible anymore, unless you go directly to the URL %s." : "Assicurati di configurare un utente amministrativo che possa accedere all'istanza tramite SSO. L'accesso con il tuo account normale %s non sarà più possibile a meno che tu vada direttamente all'URL %s.",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Scegli se vuoi autenticarti utilizzando il fornitore SAML integratore in Nextcloud o se vuoi autenticarti utilizzando una variabile d'ambiente.",
    "Use built-in SAML authentication" : "Usa autenticazione SAML integrata",
    "Use environment variable" : "Usa variabile d'ambiente",
    "Global settings" : "Impostazioni globali",
    "Remove identity provider" : "Rimuovi fornitore di identità",
    "Add identity provider" : "Aggiungi fornitore di identità",
    "General" : "Generale",
    "Service Provider Data" : "Dati del fornitore di servizi",
    "If your Service Provider should use certificates you can optionally specify them here." : "Se il tuo fornitore di servizi utilizza i certificati, puoi specificarli qui.",
    "Show Service Provider settings…" : "Mostra impostazioni fornitore di servizi...",
    "Name ID format" : "Formato ID Nome",
    "Identity Provider Data" : "Dati del fornitore di identità",
    "Configure your IdP settings here." : "Configura qui le tue impostazioni IdP.",
    "Identifier of the IdP entity (must be a URI)" : "Identificatore dell'entità IdP (deve essere un URI)",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "Destinazione dell'URL dell'IdP dove lo SP invierà il messaggio di richiesta di autenticazione",
    "Show optional Identity Provider settings…" : "Mostra impostazioni opzionali del fornitore di identità...",
    "URL Location of the IdP where the SP will send the SLO Request" : "Posizione dell'URL dell'IdP dove lo SP invierà la richiesta SLO",
    "URL Location of the IDP's SLO Response" : "Posizione dell'URL della risposta SLO dell'IDP",
    "Public X.509 certificate of the IdP" : "Certificato X.509 dell'IdP",
    "Attribute mapping" : "Associazione degli attributi",
    "If you want to optionally map attributes to the user you can configure these here." : "Se vuoi associare, in modo facoltativo, gli attributi all'utente, puoi configurarli qui.",
    "Show attribute mapping settings…" : "Mostra le impostazioni di associazione degli attributi...",
    "Security settings" : "Impostazioni di sicurezza",
    "For increased security we recommend enabling the following settings if supported by your environment." : "Per una maggiore sicurezza, consigliamo di abilitare le seguenti impostazioni, se supportate dal tuo ambiente.",
    "Show security settings…" : "Mostra impostazioni di sicurezza...",
    "Signatures and encryption offered" : "Firme e cifratura offerte",
    "Signatures and encryption required" : "Firme e cifratura richieste",
    "Download metadata XML" : "Scarica XML metadati",
    "Reset settings" : "Ripristina impostazioni",
    "Metadata invalid" : "Metadati non validi",
    "Metadata valid" : "Metadati validi",
    "Error" : "Errore",
    "Account not provisioned." : "Account non generato.",
    "Your account is not provisioned, access to this service is thus not possible." : "Il tuo account non è stato generato, l'accesso a questo servizio è perciò impossibile",
    "Login options:" : "Opzioni di accesso:",
    "Choose a authentication provider" : "Scegli un fornitore di autenticazione"
},
"nplurals=3; plural=n == 1 ? 0 : n != 0 && n % 1000000 == 0 ? 1 : 2;");
