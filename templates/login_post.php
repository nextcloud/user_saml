<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @var array $_
 * @var IL10N $l
 *
 */

use OCP\IL10N;

p($l->t('Please wait while you are redirected to the SSO server.'));
?>

<form action="<?= $_['ssoUrl'] ?>" method="post">
	<input type="hidden" name="SAMLRequest" value="<?= $_['samlRequest'] ?>" />
	<input type="hidden" name="RelayState" value="<?= $_['relayState'] ?>" />
	<input type="hidden" name="SigAlg" value="<?= $_['sigAlg'] ?>" />
	<input type="hidden" name="Signature" value="<?= $_['signature'] ?>" />
	<noscript>
		<p>
			<?php p($l->t('JavaScript is disabled in your browser. Please enable it to continue.')) ?>
		</p>
		<input type="submit" value="Continue" />
	</noscript>
</form>
<script nonce="<?= $_['nonce'] ?>">
	document.addEventListener('DOMContentLoaded', function() {
		document.forms[0].submit()
	})
</script>
