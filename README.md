# php-router
Simple, elegant, connect-style router for PHP.

Coming back to PHP from a JavaScript/Node perspective, I like this approach to routing:

<?php

require_once('./include/rest.php');

function endpoint($req,$res) {
	$res->send('Bonjour le monde');
}

$app = new REST('/');
$app->get('/',endpoint);

$app->route();

?>
