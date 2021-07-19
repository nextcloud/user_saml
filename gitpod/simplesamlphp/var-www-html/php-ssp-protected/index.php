<?php
  ini_set('display_errors', 1);
  error_reporting(E_ALL ^ E_NOTICE);
  require_once('/var/simplesamlphp/lib/_autoload.php');
  $as = new SimpleSAML_Auth_Simple('ldap');

  $as->requireAuth();
  $attributes = $as->getAttributes();
?>
<html>
<body>
	<p><a href="/">Main Menu</a></p>
<?php print_r($attributes); ?>

</body>
</html>