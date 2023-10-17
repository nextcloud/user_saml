OC.L10N.register(
    "user_saml",
    {
    "This user account is disabled, please contact your administrator." : "Ce compte utilisateur est désactivé, veuillez contacter votre administrateur.",
    "Saved" : "Sauvegardé",
    "Could not save" : "Impossible de sauvegarder",
    "Provider" : "Fournisseur",
    "Unknown error, please check the log file for more details." : "Erreur inconnue, veuillez vérifier le fichier journal pour plus de détails.",
    "Direct log in" : "Connexion directe",
    "SSO & SAML log in" : "Connexion SSO & SAML",
    "This page should not be visited directly." : "Cette page ne devrait pas être accessible directement.",
    "Provider " : "Fournisseur",
    "X.509 certificate of the Service Provider" : "Certificat X.509 du fournisseur de service",
    "Private key of the Service Provider" : "Clé privée du fournisseur de service",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "Indique que le \"nameID\" de <samlp:logoutRequest> envoyé par ce SP sera chiffré.",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "Indique que le <samlp:AuthnRequest> de messages envoyé par SP va être signé. [Métadonnée du SP va donner cette info]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "Indique si le message <samlp:logoutRequest> envoyé par ce SP sera signé.",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "Indique si le message <samlp:logoutResponse> envoyé par ce SP sera signé.",
    "Whether the metadata should be signed." : "Si les méta-données peuvent-être signées.",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "Indique que les éléments <samlp:Response>, <samlp:LogoutRequest> et <samlp:LogoutResponse> reçus par ce SP doivent être signés.",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "Indique que les éléments <saml:Assertion> reçus par ce SP doivent être signés.[Méta-données du SP offrent cette info]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "Indique que les éléments <saml:Assertion> reçus par ce SP doivent être chiffrés.",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : "Indique que l'élément NameID sur la réponse SAML reçu par ce SP doit être présent.",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "Indique que l'élément NameID sur la réponse SAML reçu par ce SP doit être chiffré.",
    "Indicates if the SP will validate all received XML." : "Indique si le SP validera tous les XML reçus.",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "ADFS encode les données du lien SAML en lettre minuscule alors que la boîte à outil utilise par défaut les lettres majuscules. Activez la compatibilité ADFS pour la vérification de la signature.",
    "Algorithm that the toolkit will use on signing process." : "Algorithme que la boîte à outils utilisera lors du processus de signature.",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "Récupération des paramètres de requête depuis $_SERVER. Certains serveurs SAML le demande pour les requêtes SLO.",
    "Attribute to map the UID to." : "Attribut pour relier l'UID.",
    "Only allow authentication if an account exists on some other backend (e.g. LDAP)." : "Ne permettre l'authentification d'un compte que s'il existe sur un autre système d'authentification. (ex : LDAP)",
    "Attribute to map the displayname to." : "Attribut pour relier le nom d'utilisateur.",
    "Attribute to map the email address to." : "Attribut pour relier l'adresse mail.",
    "Attribute to map the quota to." : "Attribut pour relier le quota.",
    "Attribute to map the users groups to." : "Attribut pour relier les groupes d'utilisateurs.",
    "Attribute to map the users home to." : "Attribut pour relier le domicile des utilisateurs.",
    "Attribute to map the users MFA login status" : "Attribut pour relier l'état de connexion AMF",
    "Reject members of these groups. This setting has precedence over required memberships." : "Rejeter les membres de ces groupes. Ce paramètre prévaut sur les appartenances requises.",
    "Group A, Group B, …" : "Groupe A, Groupe B, ...",
    "Require membership in these groups, if any." : "Exiger l'appartenance à ce·s groupe·s, si défini·s.",
    "Email address" : "Adresse e-mail",
    "Encrypted" : "Chiffré",
    "Entity" : "Entité",
    "Kerberos" : "Kerberos",
    "Persistent" : "Persistant",
    "Transient" : "En transit",
    "Unspecified" : "Non spécifié",
    "Windows domain qualified name" : "Nom de domaine Windows",
    "X509 subject name" : "Nom du sujet X509",
    "Use SAML auth for the %s desktop clients (requires user re-authentication)" : "Utiliser l'authentification SAML pour le client bureau de %s (requiert une ré-authentification de l'utilisateur)",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "Nom d'affichage facultatif du fournisseur d'identité (par défaut : \"Connexion SSO & SAML\")",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "Autoriser l'utilisation de plusieurs systèmes d'authentification (ex: LDAP)",
    "SSO & SAML authentication" : "Authentification SSO & SAML",
    "Authenticate using single sign-on" : "Authentification SSO",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "\tEn utilisant l'application SSO & SAML de votre Nextcloud, vous pouvez facilement intégrer votre solution Single-Sign-On existante avec Nextcloud. En outre, vous pouvez utiliser le fournisseur d'utilisateurs LDAP Nextcloud pour conserver une meilleur simplicité pour les utilisateurs. (par exemple quand ils partageant)\nPour le moment, seuls les fournisseurs suivants sont testés et pris en charge. \n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentification via une variable d'environnement\n\t* Kerberos (mod_auth_kerb)\n\tTout autre fournisseur qui s'authentifie à l'aide d'une variable d'environnement\n\nBien que théoriquement, tout autre fournisseur d'authentification mettant en œuvre l'une ou l'autre de ces normes soit compatible, veuillez noter que leur compatibilité n'est pas garantie,  car ils ne sont pas tester par nos équipes.",
    "Open documentation" : "Voir la documentation",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "Assurez-vous de configurer un utilisateur administratif pouvant accéder à l'instance par authentification unique (SSO). La connexion avec votre compte %s normal ne sera plus possible, à moins que vous n'ayez activé \"%s\" ou que vous vous rendiez directement à l'adresse %s.",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you go directly to the URL %s." : "Assurez-vous de configurer un utilisateur administratif pouvant accéder à l'instance par authentification unique (SSO). La connexion avec votre compte %s normal ne sera plus possible, à moins que vous vous rendiez directement à l'adresse %s.",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Veuillez choisir si vous voulez vous authentifier en utilisant le fournisseur SAML intégré à Nextcloud ou si vous voulez vous authentifier avec une variable d'environnement.",
    "Use built-in SAML authentication" : "Utiliser l'authentification SAML intégrée",
    "Use environment variable" : "Utiliser une variable d’environnement",
    "Global settings" : "Paramètres généraux",
    "Remove identity provider" : "Retirer le fournisseur d'identité",
    "Add identity provider" : "Ajouter le fournisseur d'identité",
    "General" : "Général",
    "Service Provider Data" : "Service du Fournisseur de Données",
    "If your Service Provider should use certificates you can optionally specify them here." : "Si votre fournisseur de service utilise des certificats, vous pouvez les indiquer ici.",
    "Show Service Provider settings…" : "Afficher les options du fournisseur de service...",
    "Name ID format" : "Format de l'ID du nom",
    "Identity Provider Data" : "Fournisseur de données d'identité",
    "Configure your IdP settings here." : "Configurez vos options IdP ici.",
    "Identifier of the IdP entity (must be a URI)" : "Identifiant de l'entité IdP (doit être une URI)",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "URL cible du fournisseur d'identités à qui le fournisseur de service enverra la requête d'authentification",
    "Show optional Identity Provider settings…" : "Afficher les paramètres optionnels du fournisseur d'identité...",
    "URL Location of the IdP where the SP will send the SLO Request" : "URL du fournisseur d'identité où le fournisseur de service enverra la requête de déconnexion SLO",
    "URL Location of the IDP's SLO Response" : "URL de la réponse SLO du fournisseur d’identité",
    "Public X.509 certificate of the IdP" : "Certificat public X.509 de l'IdP",
    "Attribute mapping" : "Mappage des attributs",
    "If you want to optionally map attributes to the user you can configure these here." : "Si vous préférez relier les attributs à l'utilisateur, vous pouvez les configurer ici.",
    "Show attribute mapping settings…" : "Afficher les paramètres de mappage des attributs...",
    "Security settings" : "Paramètres de sécurité",
    "For increased security we recommend enabling the following settings if supported by your environment." : "Pour augmenter la sécurité nous recommandons d'activer les paramètres suivants s'ils sont supportés par votre environnement.",
    "Show security settings…" : "Afficher les paramètres de sécurité...",
    "Signatures and encryption offered" : "Signatures et chiffrement proposés",
    "Signatures and encryption required" : "Signatures et chiffrement obligatoire",
    "User filtering" : "Filtrage des utilisateurs",
    "If you want to optionally restrict user login depending on user data, configure it here." : "Si, optionnellement, vous souhaitez restreindre la connexion sur la base des données utilisateurs, configurez le filtrage ici.",
    "Show user filtering settings …" : "Afficher les paramètres de filtrage des utilisateurs...",
    "Download metadata XML" : "Télécharger les méta-données XML",
    "Reset settings" : "Réinitialiser les paramètres",
    "Metadata invalid" : "Méta-données invalides",
    "Metadata valid" : "Méta-données valides",
    "Error" : "Erreur",
    "Access denied." : "Accès refusé.",
    "Your account is denied, access to this service is thus not possible." : "Votre compte est rejeté, accéder à ce service n'est donc pas possible.",
    "Account not provisioned." : "Compte non approvisionné.",
    "Your account is not provisioned, access to this service is thus not possible." : "Votre compte n'est pas approvisionné, l'accès à ce service n'est donc pas possible.",
    "Login options:" : "Options de connexion :",
    "Choose a authentication provider" : "Choisir un fournisseur d'authentification"
},
"nplurals=3; plural=(n == 0 || n == 1) ? 0 : n != 0 && n % 1000000 == 0 ? 1 : 2;");
