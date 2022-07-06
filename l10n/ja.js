OC.L10N.register(
    "user_saml",
    {
    "This user account is disabled, please contact your administrator." : "このユーザーアカウントは無効です。管理者に連絡してください。",
    "Saved" : "保存しました",
    "Could not save" : "保存できませんでした",
    "Provider" : "プロバイダー",
    "Unknown error, please check the log file for more details." : "不明なエラー、詳細はログファイルを確認してください。",
    "Direct log in" : "ダイレクトログイン",
    "SSO & SAML log in" : "SSO & SAML log in",
    "This page should not be visited directly." : "このページには直接アクセスしないでください。",
    "Provider " : "プロバイダー",
    "X.509 certificate of the Service Provider" : "サービスプロバイダーのX.509 証明書",
    "Private key of the Service Provider" : "サービスプロバイダーの秘密鍵",
    "Indicates that the nameID of the <samlp:logoutRequest> sent by this SP will be encrypted." : "このSPによって送信された <samlp:logoutRequest> のnameIDが暗号化されることを示します。",
    "Indicates whether the <samlp:AuthnRequest> messages sent by this SP will be signed. [Metadata of the SP will offer this info]" : "このSPによって送信された <samlp:AuthnRequest> メッセージが署名されるかどうかを示します。[SPのメタデータがこの情報を提供する]",
    "Indicates whether the  <samlp:logoutRequest> messages sent by this SP will be signed." : "このSPによって送信された  <samlp:logoutRequest> メッセージが署名されるかどうかを示します。",
    "Indicates whether the  <samlp:logoutResponse> messages sent by this SP will be signed." : "このSPによって送信された <samlp:logoutResponse> メッセージが署名されるかどうかを示します。",
    "Whether the metadata should be signed." : "メタデータに署名する必要があるかどうか。",
    "Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and <samlp:LogoutResponse> elements received by this SP to be signed." : "このSPが受信した<samlp:Response>、<samlp:LogoutRequest>、および<samlp:LogoutResponse>要素が署名されるための要件を示します。",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be signed. [Metadata of the SP will offer this info]" : "このSPによって受信された<saml:Assertion>要素が署名されるための要件を示します。 [SPのメタデータはこの情報を提供する]",
    "Indicates a requirement for the <saml:Assertion> elements received by this SP to be encrypted." : "このSPが受信した<saml:Assertion>要素を暗号化するための要件を示します。",
    " Indicates a requirement for the NameID element on the SAMLResponse received by this SP to be present." : "このSPによって受信されたSAMLResponse上のNameID要素が存在する必要があることを示します。",
    "Indicates a requirement for the NameID received by this SP to be encrypted." : "このSPによって受信されたNameIDが暗号化されるための要件を示します。",
    "Indicates if the SP will validate all received XML." : "SPが受信したすべてのXMLを検証するかどうかを示します。",
    "ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses uppercase. Enable for ADFS compatibility on signature verification." : "ADFS URL- SAMLデータを小文字で符号化し、ツールキットはデフォルトで大文字を使用します。 署名検証でADFSとの互換性を有効にする。",
    "Algorithm that the toolkit will use on signing process." : "ツールキットが署名処理で使用するアルゴリズム。",
    "Retrieve query parameters from $_SERVER. Some SAML servers require this on SLO requests." : "クエリパラメータを $_SERVER から取得します。SAMLサーバの中には、SLOリクエストでこれを要求するものもあります。",
    "Attribute to map the UID to." : "UIDをマップする属性。",
    "Only allow authentication if an account exists on some other backend (e.g. LDAP)." : "他のバックエンドにアカウントが存在する場合のみ、認証を許可します。 (例: LDAP)",
    "Attribute to map the displayname to." : "表示名をにマップする属性。",
    "Attribute to map the email address to." : "電子メールアドレスをマップする属性。",
    "Attribute to map the quota to." : "クオータをマップする属性。",
    "Attribute to map the users groups to." : "ユーザーグループをマップする属性。",
    "Attribute to map the users home to." : "ユーザーをホームにマップするための属性。",
    "Email address" : "メールアドレス",
    "Encrypted" : "暗号化",
    "Entity" : "エンティティ",
    "Kerberos" : "ケルベロス",
    "Persistent" : "永続性",
    "Transient" : "一時的",
    "Unspecified" : "指定なし",
    "Windows domain qualified name" : "Windowsドメイン修飾名",
    "X509 subject name" : "X509の件名",
    "Use SAML auth for the %s desktop clients (requires user re-authentication)" : "%s デスクトップクライアントにSAML認証を使用する（ユーザーの再認証が必要）",
    "Optional display name of the identity provider (default: \"SSO & SAML log in\")" : "IDプロバイダーのオプションの表示名（デフォルト： \"SSO＆SAMLログイン\"）",
    "Allow the use of multiple user back-ends (e.g. LDAP)" : "複数のユーザーのバックエンド（LDAPなど）の使用を許可する",
    "SSO & SAML authentication" : "SSOとSAML認証",
    "Authenticate using single sign-on" : "シングルサインオンを使用して認証する",
    "Using the SSO & SAML app of your Nextcloud you can make it easily possible to integrate your existing Single-Sign-On solution with Nextcloud. In addition, you can use the Nextcloud LDAP user provider to keep the convenience for users. (e.g. when sharing)\nThe following providers are supported and tested at the moment:\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **Authentication via Environment Variable**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\nWhile theoretically any other authentication provider implementing either one of those standards is compatible, we like to note that they are not part of any internal test matrix." : "NextcloudのSSO＆SAMLアプリを使用すると、既存のシングルサインオンソリューションをNextcloudと簡単に統合することができます。 さらに、Nextcloud LDAPユーザープロバイダーを使用して、ユーザーの利便性を保つことができます。 （例：共有時）\n現時点では、以下のプロバイダーがサポートおよびテストされています。\n\n* **SAML 2.0**\n\t* OneLogin\n\t* Shibboleth\n\t* Active Directory Federation Services (ADFS)\n\n* **環境変数による認証**\n\t* Kerberos (mod_auth_kerb)\n\t* Any other provider that authenticates using the environment variable\n\n理論的には、これらの規格のいずれかを実装している他の認証プロバイダーにも互換性がありますが、それらは内部テストマトリックスの一部ではないことに注意してください。",
    "Open documentation" : "ドキュメントを開く",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won't be possible anymore, unless you enabled \"%s\" or you go directly to the URL %s." : "SSOを介してインスタンスにアクセスできる管理ユーザーを必ず作成してください。 \"%s\"を有効にしないか、直接URL %sにアクセスしない限り、もう通常の%sアカウントでログインすることはできません。",
    "Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won't be possible anymore, unless you go directly to the URL %s." : "SSOを介してインスタンスにアクセスできる管理ユーザーを作成してください。URL %sに直接アクセスしないかぎり、もう通常の%sアカウントでログインすることはできません。",
    "Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable." : "Nextcloudに組み込まれているSAMLプロバイダーを使用して認証するか、環境変数を使用して認証するかを選択してください。",
    "Use built-in SAML authentication" : "組み込みのSAML認証を使用する",
    "Use environment variable" : "環境変数を使用する",
    "Global settings" : "グローバル設定",
    "Remove identity provider" : "identity providerを削除する",
    "Add identity provider" : "identity providerを追加する",
    "General" : "一般",
    "Service Provider Data" : "Service Providerデータ",
    "If your Service Provider should use certificates you can optionally specify them here." : "サービスプロバイダーが証明書を使用する必要がある場合は、オプションでここで指定することができます。",
    "Show Service Provider settings…" : "サービスプロバイダーの設定を表示しています...",
    "Name ID format" : "名前IDの形式",
    "Identity Provider Data" : "Identity Providerデータ",
    "Configure your IdP settings here." : "IdP をここで設定します。",
    "Identifier of the IdP entity (must be a URI)" : "IdPエンティティの識別子（URIでなければならない）",
    "URL Target of the IdP where the SP will send the Authentication Request Message" : "SPが認証要求メッセージを送信するIdPのURLターゲット",
    "Show optional Identity Provider settings…" : "オプションのIdentity Provider設定を表示する...",
    "URL Location of the IdP where the SP will send the SLO Request" : "URL SPがSLO要求を送信するIdPの場所",
    "URL Location of the IDP's SLO Response" : "IDPがSLOレスポンスを提供するURL",
    "Public X.509 certificate of the IdP" : "IdPの公開X.509証明書",
    "Attribute mapping" : "属性マッピング",
    "If you want to optionally map attributes to the user you can configure these here." : "オプションで属性をユーザーにマップする場合は、ここでそれらを構成できます。",
    "Show attribute mapping settings…" : "属性マッピングの設定を表示する...",
    "Security settings" : "セキュリティ設定",
    "For increased security we recommend enabling the following settings if supported by your environment." : "セキュリティを強化するため、ご使用の環境でサポートされている場合は、次の設定を有効にすることをお勧めします",
    "Show security settings…" : "セキュリティ設定を表示...",
    "Signatures and encryption offered" : "署名と暗号化を提供",
    "Signatures and encryption required" : "署名と暗号化が必要",
    "Download metadata XML" : "メタデータXMLをダウンロード",
    "Reset settings" : "設定をリセット",
    "Metadata invalid" : "メタデータが無効です",
    "Metadata valid" : "有効なメタデータ",
    "Error" : "エラー",
    "Account not provisioned." : "アカウントがプロビジョニングされていない",
    "Your account is not provisioned, access to this service is thus not possible." : "アカウントにプロビジョニングされていないため、このサービスへのアクセスはできません。",
    "Login options:" : "ログインオプション: ",
    "Choose a authentication provider" : "認証プロバイダーを選択する"
},
"nplurals=1; plural=0;");
