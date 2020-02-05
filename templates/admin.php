<?php
script('user_saml', 'admin');
style('user_saml', 'admin');

/** @var array $_ */
?>
<form id="user-saml" class="section" action="#" method="post" data-type="<?php p($_['type']) ?>">
	<h2 class="inlineblock"><?php p($l->t('SSO & SAML authentication')); ?></h2>
	<a target="_blank" rel="noreferrer" class="icon-info"
	   title="<?php p($l->t('Open documentation'));?>"
	   href="https://portal.nextcloud.com/article/configuring-single-sign-on-10.html"></a>

	<div id="user-saml-save-indicator" class="msg success inlineblock" style="display: none;"><?php p($l->t('Saved')); ?></div>



	<div class="warning hidden" id="user-saml-warning-admin-user">
		<?php
		$url = \OC::$server->getURLGenerator()->linkToRouteAbsolute('core.login.showLoginForm') . '?direct=1';
		$url = '<a href="' . $url . '">' . \OCP\Util::sanitizeHTML($url) . '</a>';
		if (isset($_['general']['allow_multiple_user_back_ends']['text'])) {
			print_unescaped(
				$l->t(
					'Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won\'t be possible anymore, unless you enabled "%s" or you go directly to the URL %s.',
					[
						\OCP\Util::sanitizeHTML($theme->getEntity()),
						\OCP\Util::sanitizeHTML($_['general']['allow_multiple_user_back_ends']['text']),
						$url,
					]
				)
			);
		} else {
			print_unescaped(
				$l->t(
					'Make sure to configure an administrative user that can access the instance via SSO. Logging-in with your regular %s account won\'t be possible anymore, unless you go directly to the URL %s.',
					[
						\OCP\Util::sanitizeHTML($theme->getEntity()),
						$url,
					]
				)
			);
		}
		?>
	</div>

	<div id="user-saml-choose-type" class="hidden">
		<?php p($l->t('Please choose whether you want to authenticate using the SAML provider built-in in Nextcloud or whether you want to authenticate against an environment variable.')) ?>
		<br/>
		<button id="user-saml-choose-saml"><?php p($l->t('Use built-in SAML authentication')) ?></button>
		<button id="user-saml-choose-env"><?php p($l->t('Use environment variable')) ?></button>
	</div>

	<div id="user-saml-global" class="hidden">
		<h3><?php p($l->t('Global settings')) ?></h3>
		<?php foreach($_['general'] as $key => $attribute): ?>
			<?php if($attribute['type'] === 'checkbox' && $attribute['global']): ?>
				<p>
					<input type="checkbox" data-key="<?php p($key)?>" id="user-saml-general-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '0')) ?>">
					<label for="user-saml-general-<?php p($key)?>"><?php p($attribute['text']) ?></label><br/>
				</p>
			<?php elseif($attribute['type'] === 'line' && isset($attribute['global'])): ?>
				<p>
					<input data-key="<?php p($key)?>" name="<?php p($key) ?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '')) ?>" type="text" <?php if(isset($attribute['required']) && $attribute['required'] === true): ?>class="required"<?php endif;?> placeholder="<?php p($attribute['text']) ?>"/>
				</p>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<ul class="account-list hidden">
		<?php foreach ($_['providers'] as $provider) { ?>
		<li data-id="<?php p($provider['id']); ?>">
			<a href="#"><?php p($provider['name']); ?></a>
		</li>
		<?php } ?>
		<li class="remove-provider"><a data-js="remove-idp" class="icon-delete"><span class="hidden-visually"><?php p($l->t('Remove identity provider')); ?></span></a></li>
		<li class="add-provider"><a href="#" class="button"><span class="icon-add"></span> <?php p($l->t('Add identity provider')); ?></a></li>
	</ul>

	<div id="user-saml-settings" class="hidden">

		<div id="user-saml-general" class="hidden">
			<h3>
				<?php p($l->t('General')) ?>
			</h3>
			<?php foreach($_['general'] as $key => $attribute): ?>
				<?php if($attribute['type'] === 'checkbox' && !$attribute['global']): ?>
					<p>
						<input type="checkbox" data-key="<?php p($key)?>" id="user-saml-general-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '0')) ?>">
						<label for="user-saml-general-<?php p($key)?>"><?php p($attribute['text']) ?></label><br/>
					</p>
				<?php elseif($attribute['type'] === 'line' && !isset($attribute['global'])): ?>
					<p>
						<input data-key="<?php p($key)?>" name="<?php p($key) ?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'general-'.$key, '')) ?>" type="text" <?php if(isset($attribute['required']) && $attribute['required'] === true): ?>class="required"<?php endif;?> placeholder="<?php p($attribute['text']) ?>"/>
					</p>
				<?php endif; ?>
			<?php endforeach; ?>

			<!-- FIXME: Add "Disable timeout from SAML" switch (checked by default)-->
		</div>

		<div id="user-saml-sp">
			<h3><?php p($l->t('Service Provider Data')) ?></h3>
			<p>
				<?php print_unescaped($l->t('If your Service Provider should use certificates you can optionally specify them here.')) ?>
				<span class="toggle"><?php p($l->t('Show Service Provider settings…')) ?></span>
			</p>

			<div class="hidden">
				<label for="user-saml-nameidformat"><?php p($l->t('Name ID format')) ?></label><br/>
				<select id="user-saml-nameidformat"
						name="name-id-format">
					<?php foreach($_['name-id-formats'] as $key => $value): ?>
					<option value="<?php p($key) ?>"
						<?php if ($value['selected'] ?? false) { p("selected"); } ?> ><?php p($value['label']) ?></option>
					<?php endforeach; ?>
				</select>
				<?php foreach($_['sp'] as $key => $text): ?>
					<p>
						<textarea name="<?php p($key) ?>" placeholder="<?php p($text) ?>"><?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'sp-'.$key, '')) ?></textarea>
					</p>
				<?php endforeach; ?>
			</div>
		</div>
		<div id="user-saml-idp">
			<h3><?php p($l->t('Identity Provider Data')) ?></h3>
			<p>
				<?php print_unescaped($l->t('Configure your IdP settings here.')) ?>
							</p>

			<p><input data-key="idp-entityId" name="entityId" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-entityId', '')) ?>" type="text" class="required" placeholder="<?php p($l->t('Identifier of the IdP entity (must be a URI)')) ?>"/></p>
			<p><input name="singleSignOnService.url" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-singleSignOnService.url', '')) ?>"  type="text" class="required" placeholder="<?php p($l->t('URL Target of the IdP where the SP will send the Authentication Request Message')) ?>"/></p>
			<p><span class="toggle"><?php p($l->t('Show optional Identity Provider settings…')) ?></span></p>
			<div class="hidden">
				<p><input name="singleLogoutService.url" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-singleLogoutService.url', '')) ?>" type="text" placeholder="<?php p($l->t('URL Location of the IdP where the SP will send the SLO Request')) ?>"/></p>
				<p><textarea name="x509cert" placeholder="<?php p($l->t('Public X.509 certificate of the IdP')) ?>"><?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'idp-x509cert', '')) ?></textarea></p>
			</div>
		</div>

		<div id="user-saml-attribute-mapping" class="hidden">
			<h3><?php p($l->t('Attribute mapping')) ?></h3>
			<p>
				<?php print_unescaped($l->t('If you want to optionally map attributes to the user you can configure these here.')) ?>
				<span class="toggle"><?php p($l->t('Show attribute mapping settings…')) ?></span>
			</p>

			<div class="hidden">
				<?php foreach($_['attribute-mapping'] as $key => $attribute): ?>
					<?php
					if($attribute['type'] === 'line'): ?>
					<p>
						<input name="<?php p($key) ?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'saml-attribute-mapping-'.$key, '')) ?>" type="text" <?php if(isset($attribute['required']) && $attribute['required'] === true): ?>class="required"<?php endif;?> placeholder="<?php p($attribute['text']) ?>"/>
					</p>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>

		<div id="user-saml-security">
			<h3><?php p($l->t('Security settings')) ?></h3>
			<p>
				<?php print_unescaped($l->t('For increased security we recommend enabling the following settings if supported by your environment.')) ?>
				<span class="toggle"><?php p($l->t('Show security settings…')) ?></span>
			</p>
			<div class="indent hidden">
				<h4><?php p($l->t('Signatures and encryption offered')) ?></h4>
				<?php foreach($_['security-offer'] as $key => $text): ?>
					<p>
						<input type="checkbox" id="user-saml-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '0')) ?>" class="checkbox">
						<label for="user-saml-<?php p($key)?>"><?php p($text) ?></label><br/>
					</p>
				<?php endforeach; ?>
				<h4><?php p($l->t('Signatures and encryption required')) ?></h4>
				<?php foreach($_['security-required'] as $key => $text): ?>
					<p>
						<input type="checkbox" id="user-saml-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '0')) ?>" class="checkbox">
						<label for="user-saml-<?php p($key)?>"><?php p($text) ?></label>
					</p>
				<?php endforeach; ?>
				<h4><?php p($l->t('General')) ?></h4>
				<?php foreach($_['security-general'] as $key => $attribute): ?>
					<?php if (is_array($attribute) && $attribute['type'] === 'line') { ?>
						<?php $text = $attribute['text'] ?>
						<p>
							<label><?php p($attribute['text']) ?></label><br />
							<input data-key="<?php p($key)?>" name="<?php p($key) ?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '')) ?>" type="text" <?php if(isset($attribute['required']) && $attribute['required'] === true): ?>class="required"<?php endif;?> placeholder="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
						</p>
					<?php } else { ?>
						<?php $text = $attribute ?>
						<p>
							<input type="checkbox" id="user-saml-<?php p($key)?>" name="<?php p($key)?>" value="<?php p(\OC::$server->getConfig()->getAppValue('user_saml', 'security-'.$key, '0')) ?>" class="checkbox">
							<label for="user-saml-<?php p($key)?>"><?php p($text) ?></label><br/>
						</p>
					<?php } ?>
				<?php endforeach; ?>
			</div>
		</div>

		<a id="get-metadata" data-base="<?php p(\OC::$server->getURLGenerator()->linkToRoute('user_saml.SAML.getMetadata')); ?>"
		   href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('user_saml.SAML.getMetadata', ['idp' => $_['providers'][0]['id']])) ?>" class="button">
			<?php p($l->t('Download metadata XML')) ?>
		</a>

		<button id="user-saml-reset-settings"><?php p($l->t('Reset settings')) ?></button>


		<span class="warning hidden" id="user-saml-settings-incomplete"><?php p($l->t('Metadata invalid')) ?></span>
		<span class="success hidden" id="user-saml-settings-complete"><?php p($l->t('Metadata valid')) ?></span>
	</div>
</form>
