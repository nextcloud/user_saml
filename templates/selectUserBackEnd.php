<?php
style('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var $l \OCP\IL10N */
?>

<div id="saml-select-user-back-end">

<h1>Chose login option:</h1>

<div class="login-option">
	<a href="<?php p($_['directLogin']['url']); ?>"><?php p($_['directLogin']['display-name']); ?></a>
</div>

<div class="login-option">
	<a href="<?php p($_['ssoLogin']['url']); ?>"><?php p($_['ssoLogin']['display-name']); ?></a>
</div>

</div>
