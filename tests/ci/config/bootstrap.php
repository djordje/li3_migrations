<?php

define('LITHIUM_APP_PATH', dirname(dirname(__DIR__)));
define('LITHIUM_LIBRARY_PATH', dirname(LITHIUM_APP_PATH) . '/libraries');

if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
	$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
	$message .= __FILE__ . ".  It should point to the directory containing your ";
	$message .= "/libraries directory.";
	throw new ErrorException($message);
}

use lithium\core\Libraries;
use lithium\data\Connections;

Libraries::add('lithium');
Libraries::add('app', array('default' => true));
Libraries::add('li3_fixtures');
Libraries::add('li3_migrations');

Connections::add('default', array(
	'type' => 'database',
	'adapter' => 'MySql',
	'host' => 'localhost',
	'login' => 'root',
	'password' => '',
	'database' => 'li3migrations_test',
	'encoding' => 'UTF-8'
));