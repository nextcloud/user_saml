<?php
$customTemplate = __DIR__ . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . basename(__FILE__);
if (file_exists($customTemplate)):
	include $customTemplate;
else:
?>
<ul>
	<li class="error">
		<?php p($l->t('Authentication Error')) ?><br>
		<p class="hint"><?php p($_['message']) ?></p>
	</li>
</ul>
<?php
endif;
?>