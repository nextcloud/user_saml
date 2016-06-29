<?php
script('user_saml', 'admin');
style('user_saml', 'admin');

/** @var array $_ */
?>
<form id="user-saml" class="section" action="#" method="post">
	<h2><?php p($l->t('SAML')); ?></h2>
	<div id="user-saml-save-indicator" class="msg success inlineblock" style="display: none;">Saved</div>

	<div id="user-saml-settings">
		<ul>
			<li><a href="#user-saml-sp"><?php p($l->t('Service Provider Data')) ?></a></li>
			<li><a href="#user-saml-idp"><?php p($l->t('Identity Provider Data')) ?></a></li>
			<li><a href="#user-saml-security"><?php p($l->t('Security settings')) ?></a></li>
			<li><a href="#user-saml-general"><?php p($l->t('General')) ?></a></li>
		</ul>
		<div id="user-saml-sp">
			<p><?php print_unescaped($l->t('If your Service Provider should use certificates you can optionally specify them here.')) ?></p>
			<?php foreach($_['sp'] as $key => $text): ?>
				<textarea name="<?php p($key) ?>" placeholder="<?php p($text) ?>"><?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'sp-'.$key, '')) ?></textarea>
			<?php endforeach; ?>
		</div>
		<div id="user-saml-idp">
			<p><?php print_unescaped($l->t('Configure your IdP settings here, all yellow input fields are required, others optional.')) ?></p>
			<input name="entityId" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-entityId', '')) ?>" type="text" class="required" placeholder="<?php p($l->t('Identifier of the IdP entity (must be a URI)')) ?>"/>
			<input name="singleSignOnService.url" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-singleSignOnService.url', '')) ?>"  type="text" class="required" placeholder="<?php p($l->t('URL Target of the IdP where the SP will send the Authentication Request Message')) ?>"/>
			<input name="singleLogoutService.url" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-singleLogoutService.url', '')) ?>" type="text" placeholder="<?php p($l->t('URL Location of the IdP where the SP will send the SLO Request')) ?>"/>
			<textarea name="x509cert" placeholder="<?php p($l->t('Public X.509 certificate of the IdP')) ?>"><?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-x509cert', '')) ?></textarea>
		</div>
		<div id="user-saml-security">
			<p><?php print_unescaped($l->t('For increased security we recommend enabling the following settings if supported by your environment.')) ?></p>

			<h3><?php p($l->t('Signatures and encryption offered')) ?></h3>
			<?php foreach($_['security-offer'] as $key => $text): ?>
				<input type="checkbox" id="user-saml-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '0')) ?>">
				<label for="user-saml-<?php p($key)?>"><?php p($text) ?></label><br/>
			<?php endforeach; ?>
			<h3><?php p($l->t('Signatures and encryption required')) ?></h3>
			<?php foreach($_['security-required'] as $key => $text): ?>
				<input type="checkbox" id="user-saml-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '0')) ?>">
				<label for="user-saml-<?php p($key)?>"><?php p($text) ?></label><br/>
			<?php endforeach; ?>
		</div>
		<div id="user-saml-general">
			<?php foreach($_['general'] as $key => $attribute): ?>
				<?php if($attribute['type'] === 'checkbox'): ?>
					<input type="checkbox" id="user-saml-general-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '0')) ?>">
					<label for="user-saml-general-<?php p($key)?>"><?php p($attribute['text']) ?></label><br/>
				<?php elseif($attribute['type'] === 'line'): ?>
					<input name="<?php p($key) ?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '')) ?>" type="text" <?php if(isset($attribute['required']) && $attribute['required'] === true): ?>class="required"<?php endif;?> placeholder="<?php p($attribute['text']) ?>"/>
				<?php endif; ?>
			<?php endforeach; ?>

			<!-- FIXME: Add "Disable timeout from SAML" switch (checked by default)-->
		</div>

		<a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('user_saml.SAML.getMetadata')) ?>" class="button"><?php p($l->t('Download metadata XML')) ?></a>
		<!-- FIXME: Add test settings -->
		<a class="button"><?php p($l->t('Test settings')) ?></a>
	</div>
</form>
