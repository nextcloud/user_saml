<?php
$customTemplate = __DIR__ . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . basename(__FILE__);
if (file_exists($customTemplate)):
	include $customTemplate;
else:
?>	
<ul>
	<li class="error">
		<?php p($l->t('Account not provisioned.')) ?><br>
		<p class="hint"><?php p($l->t('Your account is not provisioned, access to this service is thus not possible.')) ?></p>
	</li>
</ul>
<?php
endif;
?>