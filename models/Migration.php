<?php

namespace li3_migrations\models;

use li3_fixtures\test\Fixture;

/**
 * Base class for migration files
 */
abstract class Migration extends Fixture {

	/**
	 * The connection name
	 *
	 * @var string
	 */
	protected $_connection = 'default';

}

?>