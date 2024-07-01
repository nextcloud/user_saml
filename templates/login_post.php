Please wait while you are redirected to the SSO server.

<form action="<?= $_['ssoUrl'] ?>" method="post">
	<input type="hidden" name="SAMLRequest" value="<?= $_['samlRequest'] ?>" />
	<input type="hidden" name="RelayState" value="<?= $_['relayState'] ?>" />
	<input type="hidden" name="SigAlg" value="<?= $_['sigAlg'] ?>" />
	<input type="hidden" name="Signature" value="<?= $_['signature'] ?>" />
	<noscript>
		<p>JavaScript is disabled. Click the button below to continue.</p>
		<input type="submit" value="Continue" />
	</noscript>
</form>
<script nonce="<?= $_['nonce'] ?>">
	document.addEventListener('DOMContentLoaded', function() {
		document.forms[0].submit()
	})
</script>
