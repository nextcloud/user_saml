<!-- !CDSP: Override entire template for GC theming. -->
<?php
style('user_saml', 'selectUserBackEnd');
script('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var $l \OCP\IL10N */
?>
<div class="row lang">
	<a id="langbutton-fr" lang="fr">Français</a>
	<a id="langbutton-en" lang="en" style="display: none;">English</a>
</div>
<div class="row">
	<img class="image canada-brand" src="/ncloud/apps/user_saml/img/sig-blk-en.svg" alt="Symbol of the Government of Canada" />
</div>
<div class="row title">
	<h2 id="title-en" class="wb-inv">
    Living Labs Cloud Data Storage Platform
	</h2>
	<h2 id="title-fr" class="wb-inv" style="display: none;">
    Plateforme de stockage de données infonuagique des laboratoires vivants
  </h2>
</div>
<div id="wb-cont">
	<div id=englishdisclaimer lang="en" class="container">
		<h1 tabindex="0">AAFC Disclaimer</h1>
		Living Labs Cloud Data Storage Platform Terms of Use
		<br>
		Access to the Living Labs Cloud Data Storage Platform (the Platform) is restricted to authorized users only.
		Although the Platform is managed and monitored by Agricultureand Agri-Food Canada (AAFC), the data/information
		on the Platform is from sources internal and external to the Government of Canada. <br>
		<br>
		<h2>Usage</h2>
		<ul>
			<li>You are an authorized user of the Platform.</li>
			<li>The Platform may only be used to share data/information (soil quality, hydrographs, greenhouse gas
				emissions, biodiversity, etc.) that pertain to approved Living Labs initiatives and to consult the
				data/information submitted by other users.</li>
			<li>AAFC gives no warranty, express or implied, and accepts no legal liability or responsibility for the
				accuracy, completeness or usefulness of any information on the Platform.</li>
			<li>The Platform may only contain information up to the level of security Protected A and you are
				responsible to ensure that no data above that level is uploaded or saved to the Platform. Protected A
				information is information that, if compromised, could cause injury to an individual, organization or
				government. Examples include: contracts, tenders, unsolicited proposals, and pleadings. </li>
			<li>The Platform may not be used to upload or save personal information as defined under the <a
					href="http://laws-lois.justice.gc.ca/eng/acts/P-21/" class="disclaimer-link">Privacy Act</a>. This
				includes, for example, the age, marital status or address of an identifiable individual.</li>
			<li>The Platform may not be used to upload or save Protected B information or anything above. Protected B
				information is information that, if compromised, could cause serious injury to an individual, an
				organization or government. Examples include: combinations of individual data elements (of Protected A
				information), contract negotiations, risk assessments, government decision-making documents, trade
				secrets, business-confidential records from producers, industry partners, individual’s finances.</li>
			<li>Unauthorized use of the Platform, such as, but not limited to, using it for personal storage or storing
				either personal information as defined under the <a href="http://laws-lois.justice.gc.ca/eng/acts/P-21/"
					class="disclaimer-link">Privacy Act</a> or information above Protected A, may result in the user’s
				access being revoked immediately.</li>
		</ul>
		<h2>Monitoring</h2>
		<ul>
			<li> AAFC reserves the right to monitor network activity on the Platform. That monitoring will be carried
				out for various purposes, such as assessing system or network performance, protecting government
				resources, and ensuring compliance with the intent of the Platform. All blocking and monitoring will be
				done in compliance with the <a href="http://laws-lois.justice.gc.ca/eng/acts/P-21/"
					class="disclaimer-link">Privacy Act</a> and the <a
					href="http://laws-lois.justice.gc.ca/eng/Const/page-15.html#h-39" class="disclaimer-link">Canadian
					Charter of Rights and Freedoms</a>.</li>
			<li> AAFC employs software programs to monitor network traffic and to identify unauthorized attempts to
				upload or change information, or otherwise cause damage. This software receives and records the IP
				address of the computer/device that has contacted our website, the date and time of the visit and the
				pages visited. No attempt tolink these addresses with the identity of individuals visiting the Platform
				will be made unless an attempt to damage the Platform or criminal activity has been made.
				Thisinformation is collected pursuant to section 161 of the <a
					href="https://laws-lois.justice.gc.ca/eng/acts/f-11/" class="disclaimer-link">Financial
					Administration Act</a>.The information may be shared with appropriate law enforcement authorities if
				suspected criminal activities are detected. </li>
		</ul>
		<h2>Access to information</h2>
		<ul>
			<li> All information transmitted and stored on Government of Canada networks and devices, whether
				professional or personal in nature, may be accessible under the <a
					href="http://laws-lois.justice.gc.ca/eng/acts/A-1/" class="disclaimer-link">Access to Information
					Act</a> and the <a href="http://laws-lois.justice.gc.ca/eng/acts/P-21/"
					class="disclaimer-link">Privacy Act</a>, subject to exclusions and exemptions under these Acts.</li>
		</ul>
		<div class="lli-login-actions">
      By selecting Yes below, you acknowledge our AAFC Disclaimer
      <br>
      <br>
			<button id="agree" type="button" class="btn btn-primary agree" lang="en">
				Yes</button>
			<button id="disagree" type="button" class="btn btn-default" lang="en">
				No</button>
		</div>
	</div>
	<div id="frenchdisclaimer" lang="fr" class="container" style="display: none;">
		<h1 tabindex="0">Avis de non-responsabilité d’AAC</h1>
		Conditions d'utilisation de la plateforme de stockage de données en nuage des Laboratoires vivants<br>
		L'accès à la plateforme de stockage de données en nuage des Laboratoires vivants (la Plateforme) est réservé aux
		utilisateurs autorisés. Bien que la Plateforme soit gérée et surveillée par Agriculture et Agroalimentaire
		Canada (AAC), les données/informations qu'elle contient proviennent de sources internes et externes du
		gouvernement du Canada.<br>
		<br>
		<h2>Utilisation</h2>
		<ul>
			<li> Vous êtes un utilisateur autorisé de la Plateforme</li>
			<li> La Plateforme peut seulement être utilisée pour partager des données/informations (qualité des sols,
				hydrogrammes, émissions de gaz à effet de serre, biodiversité, etc.) qui se rapportent aux initiatives
				approuvées des laboratoires vivants et pour consulter les données/informations soumises par d'autres
				utilisateurs.</li>
			<li> AAC ne donne aucune garantie, expresse ou implicite, et n'accepte aucune responsabilité légale ou
				responsabilité sur l'exactitude, l'exhaustivité ou l'utilité de toute information sur la Plateforme.
			</li>
			<li> La Plateforme ne peut contenir que des informations d'un niveau de sécurité maximum Protégé A et vous
				êtes responsable de vous assurer qu'aucune donnée supérieure à ce niveau n'est téléchargée ou
				enregistrée sur la Plateforme. Les informations Protégé A sont des informations qui, si elles sont
				compromises, pourraient causer un préjudice à un individu, une organisation ou un gouvernement. Exemples
				: contrats, appels d'offres, propositions non sollicitées et plaidoiries. </li>
			<li> La Plateforme ne peut pas être utilisée pour télécharger ou sauvegarder des informations personnelles
				telles que définies dans la <a href="https://laws-lois.justice.gc.ca/fra/lois/P-21/"
					class="disclaimer-link">Loi sur la protection des renseignements personnels</a>. Cela inclut, par
				exemple, l'âge, l'état civil ou l'adresse d'une personne identifiable.</li>
			<li> La plateforme ne peut pas être utilisée pour télécharger ou sauvegarder des informations protégées B ou
				toute autre information ci-dessus. Les informations Protégé B sont des informations qui, si elles sont
				compromises, pourraient causer un préjudice grave à un individu, une organisation ou un gouvernement.
				Exemples : combinaisons d'éléments de données individuels (d'informations Protégé A), négociations de
				contrats, évaluations des risques, documents de décision du gouvernement, secrets commerciaux, documents
				commerciaux confidentiels des producteurs, partenaires industriels, aspects financiers d'un particulier.
			</li>
			<li> L'utilisation non autorisée de la Plateforme, telle que, mais non limitée à, l'utilisation pour le
				stockage personnel ou le stockage soit d'informations personnelles telles que définies dans la <a
					href="https://laws-lois.justice.gc.ca/fra/lois/P-21/" class="disclaimer-link">Loi sur la protection
					des renseignements personnels</a>, soit d'informations au-dessus du niveau Protégé A, peut entraîner
				la révocation immédiate de votre accès.</li>
		</ul>
		<h2>Surveillance</h2>
		<ul>
			<li> AAC se réserve le droit de surveiller l'activité du réseau sur la Plateforme. Cette surveillance sera
				effectuée à des fins diverses, telles que l'évaluation de la performance du système ou du réseau, la
				protection des ressources gouvernementales et la garantie de la conformité avec l'intention de la
				Plateforme. Tout blocage et toute surveillance seront effectués conformément à la <a
					href="https://laws-lois.justice.gc.ca/fra/lois/P-21/" class="disclaimer-link">Loi sur la protection
					des renseignements personnels</a> et à la <a
					href="https://laws-lois.justice.gc.ca/fra/Const/page-15.html" class="disclaimer-link">Charte
					canadienne des droits et libertés</a>.</li>
			<li> AAC utilise des logiciels pour surveiller le trafic sur le réseau et pour identifier les tentatives non
				autorisées de téléchargement ou de modification des informations, ou pouvant causer d'autres dommages.
				Ce logiciel reçoit et enregistre l'adresse IP de l'ordinateur ou de l'appareil qui a contacté notre site
				web, la date et l'heure de la visite et les pages consultées. Aucune tentative de relier ces adresses à
				l'identité des personnes visitant la Plateforme ne sera faite à moins qu'une tentative de dommage à la
				Plateforme ou activité criminelle n'ait été faite. Ces informations sont collectées conformément à
				l'article 161 de la <a href="https://laws-lois.justice.gc.ca/fra/lois/f-11/" class="disclaimer-link">Loi
					sur la gestion des finances publiques</a>. Les informations peuvent être partagées avec les
				autorités policières appropriées si des activités criminelles suspectes sont détectées.</li>
		</ul>
		<h2>Accès à l’information</h2>
		<ul>
			<li> Toutes les informations transmises et stockées sur les réseaux et dispositifs du gouvernement du
				Canada, qu'elles soient de nature professionnelle ou personnelle, peuvent être accessibles en vertu de
				la <a href="https://laws-lois.justice.gc.ca/fra/lois/A-1/" class="disclaimer-link">Loi sur l'accès à
					l'information</a> et de la <a href="https://laws-lois.justice.gc.ca/fra/lois/P-21/"
					class="disclaimer-link">Loi sur la protection des renseignements personnels</a>, sous réserve des
				exclusions et des exemptions prévues par ces lois.</li>
		</ul>
		<div class="lli-login-actions">
      En sélectionnant oui ci-dessous, vous reconnaissez l’avis de non-responsabilité d'AAC :
      <br>
		  <br>
			<button id="agree" type="button" class="btn btn-primary agree" lang="fr">
				Oui</button>
			<button id="disagree" type="button" class="btn btn-default" lang="fr">
				Non</button>
		</div>
	</div>
</div>
<div id="saml-select-user-back-end">
	<h1><?php p($l->t('Login options:')); ?></h1>
	<?php if ($_['useCombobox']) { ?>
		<select class="login-chose-saml-idp" id="av_mode" name="avMode">
			<option value=""><?php p($l->t('Choose a authentication provider')); ?></option>
			<?php foreach ($_['loginUrls']['ssoLogin'] as $idp) { ?>
				<option value="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></option>
			<?php } ?>
			<?php if (isset($_['loginUrls']['directLogin'])): ?>
				<option value="<?php p($_['loginUrls']['directLogin']['url']); ?>">
					<?php p($_['loginUrls']['directLogin']['display-name']); ?>
				</option>
			<?php endif; ?>
		</select>
	<?php } else { ?>
		<?php if (isset($_['loginUrls']['directLogin'])): ?>
			<div class="login-option">
				<a
					href="<?php p($_['loginUrls']['directLogin']['url']); ?>"><?php p($_['loginUrls']['directLogin']['display-name']); ?></a>
			</div>
		<?php endif; ?>
		<?php
		foreach ($_['loginUrls']['ssoLogin'] as $idp) { ?>
			<div class="login-option">
				<a class="sso-login" href="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></a>
			</div>

		<?php } ?>
	<?php } ?>
</div>
<div class="row can-footer">
	<nav class="can-footer-links">
		<ul id="footer-en">
			<li><a href="https://www.canada.ca/en/contact.html">Contact information</a></li>
			<li><a href="https://www.canada.ca/en/transparency/terms.html">Terms and conditions</a></li>
			<li><a href="https://www.canada.ca/en/transparency/privacy.html">Privacy</a></li>
		</ul>
		<ul id="footer-fr" style="display: none;">
			<li><a href="https://www.canada.ca/fr/contact.html">Coordonnées</a></li>
			<li><a href="https://www.canada.ca/fr/transparence/avis.html">Avis</a></li>
			<li><a href="https://www.canada.ca/fr/transparence/confidentialite.html">Confidentialité</a></li>
		</ul>
	</nav><div class="can-footer-brand">
		<img class="footer-brand" src="/ncloud/apps/user_saml/img/wmms-blk.svg"
			alt="Symbol of the Government of Canada">
	</div>
</div>