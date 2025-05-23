OC.L10N.register(
    "user_saml",
    {
    "Saved" : "Gespeichert",
    "Could not save" : "Konnte nicht speichern",
    "Provider" : "Anbieter",
    "This user account is disabled, please contact your administrator." : "Dieses Nutzerkonto ist deaktiviert. Bitte kontaktieren Sie Ihre Administration.",
    "Unknown error, please check the log file for more details." : "Unbekannter Fehler, bitte prüfen Sie die Log-Datei für weitere Informationen.",
    "Direct log in" : "Direkte Anmeldung",
    "SSO & SAML log in" : "SSO- & SAML-Anmeldung",
    "This page should not be visited directly." : "Diese Seite sollte nicht direkt aufgerufen werden.",
    "Provider " : "Anbieter",
    "X.509 certificate of the Service Provider" : "X.509-Zertifikat des Diensteanbieters",
    "Private key of the Service Provider" : "Privater Schlüssel des Diensteanbieters",
    "Service Provider Entity ID (optional)" : "Entitiy-ID des Dienstanbieters (optional)",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "Zeigt an, dass die nameID des <samlp:logoutRequest> von diesem Diensteanbieter verschlüsselt versandt werden.",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "Zeigt an, ob die von diesem Diensteanbieter gesendeten <samlp:AuthnRequest> - Nachrichten signiert werden. [Die Metadaten des Diensteanbieters zeigen diese Infos an]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "Erfordert, dass die von diesem Diensteanbieter gesendeten <samlp:logoutRequest> Nachrichten signiert werden.",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "Zeigt an, ob die von diesem Diensteanbieter gesendeten <samlp:logoutResponse> Nachrichten signiert werden.",
    "Whether the metadata should be signed." : "Gibt an, ob die Metadaten signiert werden sollen.",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "Zeigt an, dass die von diesem SP empfangenen Elemente <samlp:Response>, <samlp:LogoutRequest> und <samlp:LogoutResponse> signiert werden müssen.",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "Erfordert, dass die <saml:Assertion> Elemente, die von diesem Diensteanbieter empfangen wurden, verschlüsselt sein müssen. [Metadaten des Diensteanbieters enthalten diese Informationen]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "Erfordert, dass die <saml:Assertion> Elemente, die von diesem Diensteanbieter empfangen wurden, verschlüsselt sein müssen.",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : "Erfordert, dass das NameID-Element der SAML-Antwort dieses Diensteanbieters vorhanden sein muss.",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "Erfordert, dass die NameID, die von diesem Diensteanbieter empfangen wird, verschlüsselt sein muss.",
    "Indicates if the SP will validate all received XML." : "Gibt an, ob der Diensteanbieter alle empfangenen XML-Inhalte überprüft.",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "ADFS kodiert SAML-URL-Daten in Kleinbuchstaben und das Toolkit nutzt als Standard Großbuchstaben. Diese Option für ADFS-Kompatibilität bei Signatur-Überprüfung aktivieren.",
    "Algorithm that the toolkit will use on signing process." : "Algorithmus, den das Toolkit beim Signieren verwendet.",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "Abfrageparameter von $_SERVER abrufen. Einige SAML-Server erfordern dies bei SLO-Anfragen.",
    "Attribute to map the UID to." : "Attribut dem die UID zugeordnet werden soll.",
    "Only allow authentication if an account exists on some other backend (e.g. LDAP)." : "Anmeldung nur erlauben, wenn ein Konto auf einem anderen Backend vorhanden ist (z.B. LDAP).",
    "Attribute to map the displayname to." : "Attribut dem der Anzeigename zugeordnet werden soll.",
    "Attribute to map the email address to." : "Attribut, dem die E-Mail-Adresse zugeordnet werden soll.",
    "Attribute to map the quota to." : "Attribut, dem das Speicherkontingent zugeordnet werden soll.",
    "Attribute to map the users home to." : "Attribut dem das zu Hause des Benutzers zugeordnet werden soll.",
    "Attribute to map the users groups to." : "Attribut, dem die Gruppen des Benutzers zugeordnet werden sollen.",
    "Attribute to map the users MFA login status" : "Attribut zur Zuordnung des MFA-Anmeldestatus des Benutzers",
    "Group Mapping Prefix, default: %s" : "Präfix Gruppenzuordnung, Standard: %s",
    "Reject members of these groups. This setting has precedence over required memberships." : "Mitglieder dieser Gruppen ablehnen. Diese Einstellung hat Vorrang vor erforderlichen Mitgliedschaften.",
    "Group A, Group B, …" : "Gruppe A, Gruppe B, …",
    "Require membership in these groups, if any." : "Erfordert die Mitgliedschaft in diesen Gruppen, falls vorhanden.",
    "Email address" : "E-Mail-Adresse",
    "Encrypted" : "Verschlüsselt",
    "Entity" : "Einheit",
    "Kerberos" : "Kerberos",
    "Persistent" : "Dauerhaft",
    "Transient" : "Flüchtig",
    "Unspecified" : "Nicht spezifiziert",
    "Windows domain qualified name" : "Windows-Domäne qualifizierter Name",
    "X509 subject name" : "X509-Subjektname ",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "Optional den Namen des Identitätsanbieters anzeigen (Standard: \"SSO- & SAML-Anmeldung\")",
    "Use POST method for SAML request (default: GET)" : "POST-Methode für SAML-Anfragen verwenden (Standard: GET)",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "Die Verwendung von mehreren Benutzerverwaltungen erlauben (z. B. LDAP)",
    "SSO & SAML authentication" : "SSO & SAML-Autorisierung",
    "Authenticate using single sign-on" : "Authentifizieren mit Single-Sign-On",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "Die SSO & SAML-App ermöglicht es Ihre bereits bestehende Singl-Sign-On-Lösung einfach in Nextcloud zu integrieren. Ausserdem kann der Nextcloud LDAP-Nutzer-Anbieter verwendet werden,  um es den Nutzern (z.B. beim eilen) besonders einfach zu machen.\nBislang werden folgende Anbieter unterstützt und sind getestet:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Anmeldung über Umgebungsvariable**\n\t* Kerberos (mod_auth_kerb)\n\t* Alle anderen Anbieter, die die Umgebungsvariable verwenden\n\nObwohl theoretisch jeder andere Anmeldungs-Anbieter  der einen der Standards implementiert hat verwendet werden kann, weisen wir darauf hin, dass diese anderen Anbieter nicht in unserer Test-Matrix berücksichtigt werden.",
    "Open documentation" : "Dokumentation öffnen",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "Es muss ein Benutzer mit Administrationsrechten vorhanden sein, der sich mittels SSO anmelden kann. Eine Anmeldung mit deinem normalen Zugang %s ist dann nicht mehr möglich, außer Sie haben \"%s\" aktiviert oder Sie gehen direkt zur URL %s.",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you go directly to the URL %s." : "Es muss ein Benutzer mit Administrationsrechten vorhanden sein, der sich mittels SSO anmelden kann. Eine Anmeldung mit Ihrem normalen Zugang %s ist dann nicht mehr möglich, es sei denn, Sie gehen direkt zur URL %s.",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Bitte auswählen ob die Authentifizierung mittels in Nextcloud integriertem SAML-Provider oder gegen eine Umgebungsvariable erfolgen soll.",
    "Use built-in SAML authentication" : "Integrierte SAML-Authentifizierung benutzen",
    "Use environment variable" : "Umgebungsvariable benutzen",
    "Global settings" : "Globale Einstellungen",
    "Remove identity provider" : "Identitätsanbieter entfernen",
    "Add identity provider" : "Identitätsanbieter hinzufügen",
    "General" : "Allgemein",
    "This feature might not work with all identity providers. Use only if your IdP specifically requires POST binding for SAML requests." : "Diese Funktion ist möglicherweise nicht mit allen Identitätsanbietern kompatibel. Verwenden Sie sie nur, wenn Ihr Identitätsanbieter eine POST-Bindung für SAML-Anfragen erfordert.",
    "Service Provider Data" : "Diensteanbieter-Daten",
    "If your Service Provider should use certificates you can optionally specify them here." : "Wenn Ihr Dienstanbieter Zertifikate verwenden soll, können Sie diese hier optional angeben.",
    "Show Service Provider settings…" : "Zeige die Diensteanbieter-Einstellungen…",
    "Name ID format" : "Name-ID-Format",
    "Identity Provider Data" : "Daten des Identitätsanbieters",
    "Identifier of the IdP entity (must be a URI)" : "Identifikationsmerkmal des Identitätsanbieters (muss eine URL sein)",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "URL-Ziel des Identitätsanbieters, an den der Diensteanbieter die Anmeldungsanfrage senden soll",
    "Show optional Identity Provider settings…" : "Optionale Einstellungen des Identitätsanbieters anzeigen …",
    "URL Location of the IdP where the SP will send the SLO Request" : "URL-Adresse des Identitätsanbieters, an den der Diensteanbieter die SLO-Anfrage senden soll",
    "URL Location of the IDP's SLO Response" : "URL-Adresse der SLO-Antwort des Identitätsanbieters",
    "Public X.509 certificate of the IdP" : "Öffentliches X.509-Zertifikat des Identitätsanbieters",
    "Request parameters to pass-through to IdP (comma separated list)" : "An IdP weiterzuleitende Anforderungsparameter (durch Kommas getrennte Liste)",
    "Attribute mapping" : "Attribute zuordnen",
    "If you want to optionally map attributes to the user you can configure these here." : "Wenn Sie optional Attribute dem Benutzer zuordnen möchten, können Sie dies hier einstellen.",
    "Show attribute mapping settings…" : "Einstellungen der Attribute-Zuordnung anzeigen …",
    "Security settings" : "Sicherheitseinstellungen",
    "For increased security we recommend enabling the following settings if supported by your environment." : "Zur Erhöhung der Sicherheit empfehlen wir, die folgenden Einstellungen zu aktivieren, sofern dies von Ihrer Installation unterstützt wird.",
    "Show security settings…" : "Sicherheitseinstellungen anzeigen …",
    "Signatures and encryption offered" : "Signaturen und Verschlüsselung werden angeboten",
    "Signatures and encryption required" : "Signaturen und Verschlüsselung erforderlich",
    "User filtering" : "Benutzerfilterung",
    "If you want to optionally restrict user login depending on user data, configure it here." : "Wenn Sie die Benutzeranmeldung abhängig von Benutzerdaten optional einschränken möchten, stellen Sie dies hier ein.",
    "Show user filtering settings …" : "Benutzer-Filtereinstellungen anzeigen…",
    "Download metadata XML" : "Lade Metadaten-XML herunter",
    "Reset settings" : "Einstellungen zurücksetzen",
    "Metadata invalid" : "Metadaten ungültig",
    "Metadata valid" : "Metadaten gültig",
    "Error" : "Fehler",
    "Please wait while you are redirected to the SSO server." : "Bitte warten Sie, während Sie zum SSO-Server weitergeleitet werden.",
    "JavaScript is disabled in your browser. Please enable it to continue." : "JavaScript ist in Ihrem Browser deaktiviert. Bitte aktivieren, um fortzufahren.",
    "Access denied." : "Zugriff verweigert.",
    "Your account is denied, access to this service is thus not possible." : "Ihr Konto ist gesperrt, der Zugriff auf diesen Dienst ist somit nicht möglich.",
    "Account not provisioned." : "Konto nicht bereitgestellt.",
    "Your account is not provisioned, access to this service is thus not possible." : "Ihr Konto wird nicht bereitgestellt. Der Zugriff ist daher nicht möglich.",
    "Login options:" : "Anmeldeoptionen:",
    "Choose an authentication provider" : "Authentifizierungsanbieter wählen",
    "Group Mapping Prefix, default: SAML_" : "Präfix Gruppenzuordnung, Standard: SAML_"
},
"nplurals=2; plural=(n != 1);");
