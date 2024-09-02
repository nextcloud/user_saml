<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use OCP\Util;

style('user_saml', 'selectUserBackEnd');
Util::addScript('user_saml', 'selectUserBackEnd');

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div id="saml-select-user-back-end">

<h1><?php p($l->t('Login options:')); ?></h1>

	<?php if ($_['useCombobox']) { ?>

		<select class="login-chose-saml-idp" id="av_mode" name="avMode">
			<option value=""><?php p($l->t('Choose an authentication provider')); ?></option>
			<?php foreach ($_['loginUrls']['ssoLogin'] as $idp) { ?>
				<option value="<?php p($idp['url']); ?>"><?php p($idp['display-name']); ?></option>
			<?php } ?>
			<?php if (isset($_['loginUrls']['directLogin'])) : ?>
				<option value="<?php p($_['loginUrls']['directLogin']['url']); ?>"><?php p($_['loginUrls']['directLogin']['display-name']); ?></option>
			<?php endif; ?>
		</select>

	<?php } else { ?>

		<?php if (isset($_['loginUrls']['directLogin'])) : ?>
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
