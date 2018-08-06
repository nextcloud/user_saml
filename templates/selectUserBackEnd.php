<?php
style('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var $l \OCP\IL10N */
?>

<div id="saml-select-user-back-end">

<h1>Choose login option:</h1>

	<?php if(isset($_['directLogin'])) : ?>
<div class="login-option">
	<a href="<?php p($_['directLogin']['url']); ?>"><?php p($_['directLogin']['display-name']); ?></a>
</div>
	<?php endif; ?>

	<?php foreach ($_['ssoLogin'] as $idp) { ?>
<div class="login-option">
	<a href="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></a>
</div>
	<?php } ?>

</div>
