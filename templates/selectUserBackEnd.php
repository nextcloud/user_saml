<?php
style('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var $l \OCP\IL10N */
?>

<div id="saml-select-user-back-end">

<h1>Choose login option:</h1>

<div class="login-option">
	<a href="<?php p($_['directLogin']); ?>"><?php p($l->t('Direct log in')); ?></a>
</div>

<div class="login-option">
	<a href="<?php p($_['ssoLogin']); ?>"><?php p($l->t('SSO & SAML log in')); ?></a>
</div>

</div>
