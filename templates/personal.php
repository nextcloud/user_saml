<?php
style('user_saml', 'personal');

script('user_saml', [
	'personal/authtoken',
	'personal/authtoken-collection',
	'personal/authtoken_view',
	'personal',
]);

/** @var array $_ */
?>

<div id="user-saml-apppasswords" class="section">
	<h2><?php p($l->t('App passwords'));?></h2>
	<span class="hidden-when-empty"><?php p($l->t("You've linked these apps."));?></span>
	<table>
		<thead class="hidden-when-empty">
		<tr>
			<th><?php p($l->t('Name'));?></th>
			<th></th>
		</tr>
		</thead>
		<tbody class="token-list icon-loading">
		</tbody>
	</table>
	<p><?php p($l->t('An app password is a passcode that gives an app or device permissions to access your %s account.', [$theme->getName()]));?></p>
	<div id="user-saml-app-password-form">
		<input id="user-saml-app-password-name" type="text" placeholder="<?php p($l->t('App name')); ?>">
		<button id="user-saml-add-app-password" class="button"><?php p($l->t('Create new app password')); ?></button>
	</div>
	<div id="user-saml-app-password-result" class="hidden">
		<span><?php p($l->t('Use the credentials below to configure your app or device.')); ?></span>
		<div class="user-saml-app-password-row">
			<span class="user-saml-app-password-label"><?php p($l->t('Password')); ?></span>
			<input id="user-saml-new-app-password" type="text" readonly="readonly"/>
			<button id="user-saml-app-password-hide" class="button"><?php p($l->t('Done')); ?></button>
		</div>
	</div>
</div>
