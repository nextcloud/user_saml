<?php
style('user_saml', 'selectUserBackEnd');
script('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var $l \OCP\IL10N */
?>

<div id="saml-select-user-back-end">

<h1>Login options:</h1>

	<?php if($_['useCombobox']) { ?>

		<select class="login-chose-saml-idp" id="av_mode" name="avMode">
			<option value=""><?php p($l->t('Choose a authentication provider')); ?></option>
			<?php foreach ($_['loginUrls']['ssoLogin'] as $idp) { ?>
				<option value="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></option>
			<?php } ?>
			<?php if(isset($_['loginUrls']['directLogin'])) : ?>
				<option value="<?php p($_['loginUrls']['directLogin']['url']); ?>"><?php p($_['loginUrls']['directLogin']['display-name']); ?></option>
			<?php endif; ?>
		</select>

	<?php } else { ?>

		<?php if(isset($_['loginUrls']['directLogin'])) : ?>
			<div class="login-option">
				<a href="<?php p($_['loginUrls']['directLogin']['url']); ?>"><?php p($_['loginUrls']['directLogin']['display-name']); ?></a>
			</div>
		<?php endif; ?>

		<?php foreach ($_['loginUrls']['ssoLogin'] as $idp) { ?>
			<div class="login-option">
				<a href="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></a>
			</div>
		<?php } ?>

	<?php } ?>

</div>
