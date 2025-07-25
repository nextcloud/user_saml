OC.L10N.register(
    "user_saml",
    {
    "Saved" : "Imehifadhiwa",
    "Could not save" : "Haikuweza kuhifadhi",
    "Provider" : "Mtoa huduma",
    "This user account is disabled, please contact your administrator." : "Akaunti hii ya mtumiaji imezimwa, tafadhali wasiliana na msimamizi wako.",
    "Unknown error, please check the log file for more details." : "Hitilafu isiyojulikana, tafadhali angalia faili ya kumbukumbu kwa maelezo zaidi.",
    "Direct log in" : "Ingia moja kwa moja",
    "SSO & SAML log in" : "Ingia kwa SSO & SAML ",
    "This page should not be visited directly." : "Ukurasa huu haupaswi kutembelewa moja kwa moja.",
    "Provider " : "Mtoa huduma",
    "X.509 certificate of the Service Provider" : "Cheti cha X.509 cha Mtoa Huduma",
    "Private key of the Service Provider" : "Ufunguo wa kibinafsi wa Mtoa Huduma",
    "Service Provider Entity ID (optional)" : "Kitambulisho cha Huluki ya Mtoa Huduma (si lazima)",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "Inaonyesha kwamba jinaID ya <samlp:logoutRequest> iliyotumwa na SP hii itasimbwa kwa njia fiche.",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "Inaonyesha kama ujumbe <samlp:AuthnRequest> uliotumwa na SP hii utatiwa saini. [Metadata ya SP itatoa habari hii]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "Inaonyesha kama ujumbe <samlp:logoutRequest> uliotumwa na SP hii utatiwa saini.",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "Inaonyesha kama ujumbe <samlp:logoutResponse> uliotumwa na SP hii utatiwa saini.",
    "Whether the metadata should be signed." : "Ikiwa metadata inapaswa kusainiwa",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "Inaonyesha mahitaji ya vipengele <samlp:Response>, <samlp:LogoutRequest> na <samlp:LogoutResponse> vilivyopokelewa na SP hii kutiwa saini",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "Inaonyesha mahitaji ya vipengele <saml:Assertion> vilivyopokelewa na SP hii kutiwa saini. [Metadata ya SP itatoa habari hii]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "Inaonyesha hitaji la vipengele <saml:Assertion> vilivyopokelewa na SP hii kusimbwa kwa njia fiche.",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : "Inaonyesha hitaji la kipengele cha NameID kwenye SAMLResponse iliyopokelewa na SP hii kuwepo.",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "Inaonyesha hitaji la NameID iliyopokelewa na SP hii kusimbwa kwa njia fiche.",
    "Indicates if the SP will validate all received XML." : "Inaonyesha ikiwa SP itathibitisha XML zote zilizopokelewa.",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "ADFS URL-Husimba data ya SAML kama herufi ndogo, na kisanduku cha zana kwa chaguomsingi hutumia herufi kubwa. Washa kwa uoanifu wa ADFS kwenye uthibitishaji wa sahihi.",
    "Algorithm that the toolkit will use on signing process." : "Algorithm ambayo zana itatumia katika mchakato wa kusaini.",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "Rejesha vigezo vya hoja kutoka $_SERVER. Baadhi ya seva za SAML zinahitaji hili kwenye maombi ya SLO.",
    "Attribute to map the UID to." : "Sifa ya kuweka UID kwenye ramani.",
    "Only allow authentication if an account exists on some other backend (e.g. LDAP)." : "Ruhusu tu uthibitishaji ikiwa akaunti ipo kwenye sehemu nyingine ya nyuma (k.m. LDAP).",
    "Attribute to map the displayname to." : "Sifa ya kuweka jina la kuonyesha kwenye ramani.",
    "Attribute to map the email address to." : "Sifa ya kuweka anwani ya barua pepe kwa ramani.",
    "Attribute to map the quota to." : "Sifa ya kuweka mgawo kwa ramani.",
    "Attribute to map the users home to." : "Sifa ya kuweka ramani ya watumiaji nyumbani.",
    "Attribute to map the users groups to." : "Sifa ya kuweka vikundi vya watumiaji kwenye ramani.",
    "Attribute to map the users MFA login status" : "Sifa ya ramani ya watumiaji MFA hali ya kuingia",
    "Group Mapping Prefix, default: %s" : "Kiambishi awali cha Ramani ya Kikundi, chaguo-msingi: %s",
    "Reject members of these groups. This setting has precedence over required memberships." : "Kataa washiriki wa vikundi hivi. Mipangilio hii ina kipaumbele zaidi ya uanachama unaohitajika.",
    "Group A, Group B, …" : "Kundi A, Kundi B,…",
    "Require membership in these groups, if any." : "Inahitaji uanachama katika vikundi hivi, kama wapo.",
    "Email address" : "Anwani ya barua pepe",
    "Encrypted" : "Imesimbwa kwa njia fiche",
    "Entity" : "Chombo",
    "Kerberos" : "Kerberos",
    "Persistent" : "Endelevu",
    "Transient" : "Kudumu",
    "Unspecified" : "Haijatajwa",
    "Windows domain qualified name" : "Jina la sifa la kikoa cha Windows",
    "X509 subject name" : "Jina la mhusika X509",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "Jina la hiari la mtoa utambulisho (chaguo-msingi: \"Ingia kwa SSO & SAML\")",
    "Use POST method for SAML request (default: GET)" : "Tumia njia ya POST kwa ombi la SAML (chaguo-msingi: GET)",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "Ruhusu matumizi ya mifumo mingi ya nyuma ya mtumiaji (mfano LDAP)",
    "SSO & SAML authentication" : "Uthibitishaji wa SSO na SAML",
    "Authenticate using single sign-on" : "Thibitisha kwa kutumia kuingia moja kwa moja",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "Kwa kutumia programu ya SSO & SAML ya Nextcloud yako unaweza kuwezesha kwa urahisi kuunganisha suluhisho lako lililopo la Kuingia Mara Moja na Nextcloud. Kwa kuongeza, unaweza kutumia mtoa huduma wa Nextcloud LDAP ili kuweka urahisi kwa watumiaji. (k.m. wakati wa kushiriki)\nWatoa huduma wafuatao wanasaidiwa na kujaribiwa kwa sasa:\n\n**SAML 2.0**\n\t* OneLogin\n\t* Shibolethi\n\t* Huduma za Shirikisho la Saraka Inayotumika (ADFS)\n\n** Uthibitishaji kupitia Kigezo cha Mazingira **\n\t* Kerberos (mod_auth_kerb)\n\t* Mtoa huduma mwingine yeyote anayeidhinisha kwa kutumia kigeu cha mazingira\n\nIngawa kinadharia mtoa huduma mwingine yeyote wa uthibitishaji anayetekeleza mojawapo ya viwango hivyo anaoana, tunapenda kutambua kwamba si sehemu ya matrix yoyote ya majaribio ya ndani.",
    "Open documentation" : " Fungua nyaraka",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "Hakikisha kuwa umeweka mipangilio ya mtumiaji wa msimamizi ambaye anaweza kufikia mfano huo kupitia SSO. Kuingia kwa akaunti yako ya kawaida ya %s hakutawezekana tena, isipokuwa ikiwa umewasha \"%s\" au uende moja kwa moja kwenye URL %s.",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account will not be possible anymore, unless you go directly to the URL %s." : "Hakikisha kuwa umeweka mipangilio ya mtumiaji wa msimamizi ambaye anaweza kufikia mfano huo kupitia SSO. Kuingia kwa akaunti yako ya kawaida ya %s hakutawezekana tena, isipokuwa ukienda moja kwa moja kwenye URL %s.",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Tafadhali chagua kama ungependa kuthibitisha kwa kutumia mtoa huduma wa SAML aliyejengewa ndani katika Nextcloud au kama unataka kuthibitisha dhidi ya utofauti wa mazingira.",
    "Use built-in SAML authentication" : "Tumia uthibitishaji wa SAML uliojengwa ndani",
    "Use environment variable" : "Tumia kigezo cha mazingira",
    "Global settings" : "Mipangilio ya ulimwengu",
    "Remove identity provider" : "Ondoa mtoa kitambulisho",
    "Add identity provider" : "Ongeza mtoa kitambulisho",
    "General" : "Kuu",
    "This feature might not work with all identity providers. Use only if your IdP specifically requires POST binding for SAML requests." : "Huenda kipengele hiki kisifanye kazi na watoa huduma wote wa vitambulisho. Tumia tu ikiwa IdP yako inahitaji POST binding kwa maombi ya SAML.",
    "Service Provider Data" : "Takwimu za Mtoa Huduma",
    "If your Service Provider should use certificates you can optionally specify them here." : "Ikiwa Mtoa Huduma wako atatumia vyeti unaweza kuvibainisha hapa kwa hiari.",
    "Show Service Provider settings…" : "Onyesha mipangilio ya Mtoa Huduma...",
    "Name ID format" : "Umbizo wa Kitambulisho cha Jina",
    "Identity Provider Data" : "Takwimu za Mtoa Utambulisho",
    "Identifier of the IdP entity (must be a URI)" : "Kitambulisho cha chombo cha IdP (lazima kiwe URI)",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "URL inayolengwa ya IdP ambapo SP itatuma Ujumbe wa Ombi la Uthibitishaji",
    "Show optional Identity Provider settings…" : "Onyesha mipangilio ya hiari ya Mtoa Utambulisho...",
    "URL Location of the IdP where the SP will send the SLO Request" : "Mahali pa URL ya IdP ambapo SP itatuma Ombi la SLO",
    "URL Location of the IDP's SLO Response" : "Mahali pa URL ya Majibu ya SLO ya IDP",
    "Public X.509 certificate of the IdP" : "Cheti cha Umma cha X.509 cha IdP",
    "Request parameters to pass-through to IdP (comma separated list)" : "Vigezo vya ombi vya kupitisha kwa IdP (orodha iliyotenganishwa kwa koma)",
    "Attribute mapping" : "Kuchora ramani ya sifa",
    "If you want to optionally map attributes to the user you can configure these here." : "Ikiwa ungependa kupanga kwa hiari sifa za mtumiaji unaweza kusanidi hizi hapa.",
    "Show attribute mapping settings…" : "Onyesha mipangilio ya ramani ya sifa…",
    "Security settings" : "Mipangilio ya usalama",
    "For increased security we recommend enabling the following settings if supported by your environment." : "Kwa usalama ulioimarishwa tunapendekeza kuwezesha mipangilio ifuatayo ikiwa inatumika na mazingira yako.",
    "Show security settings…" : "Onesha mipangilio ya usalama...",
    "Signatures and encryption offered" : "Saini na usimbaji fiche vinavyotolewa",
    "Signatures and encryption required" : "Saini na usimbaji fiche unahitajika",
    "User filtering" : "Uchujaji wa mtumiaji",
    "If you want to optionally restrict user login depending on user data, configure it here." : "Ikiwa unataka kuzuia kuingia kwa mtumiaji kwa hiari kulingana na data ya mtumiaji, isanidi hapa.",
    "Show user filtering settings …" : "Onyesha mipangilio ya kuchuja mtumiaji",
    "Download metadata XML" : "Pakua metadata ya XML",
    "Reset settings" : "Weka upya mipangilio",
    "Metadata invalid" : "Metadata ni batili",
    "Metadata valid" : "Metadata ni halali",
    "Error" : "Hitilafu",
    "Please wait while you are redirected to the SSO server." : "Tafadhali subiri wakati unaelekezwa kwenye seva ya SSO.",
    "JavaScript is disabled in your browser. Please enable it to continue." : "JavaScript imezimwa kwenye kivinjari chako. Tafadhali iwezeshe ili kuendelea.",
    "Access denied." : "Ufikiaji umekataliwa.",
    "Your account is denied, access to this service is thus not possible." : "Akaunti yako imekataliwa, ufikiaji wa huduma hii kwa hivyo hauwezekani.",
    "Account not provisioned." : "Akaunti haijatolewa.",
    "Your account is not provisioned, access to this service is thus not possible." : "Akaunti yako haijatolewa, ufikiaji wa huduma hii hauwezekani.",
    "Login options:" : "Chaguo za kuingia:",
    "Choose an authentication provider" : "Chagua mtoaji wa uthibitishaji",
    "Group Mapping Prefix, default: SAML_" : "Kiambishi awali cha Kuchora Kikundi, chaguomsingi: SAML_"
},
"nplurals=2; plural=(n != 1);");
