# php-router
Simple connect-style router for PHP.

Coming back to PHP from a JavaScript/Node perspective, I like this approach to routing:

```php
<?php

require_once('router.php');

function helloWorld($req, $res) {
	$res->send('Bonjour le monde');
}

$app = new REST('/'); # The base path
$app->get('/', helloWorld);
$app->route();

?>
```

Note: There is a way to configure this to work for ReactPHP event-loop if you want.
