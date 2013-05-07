<?php

namespace li3_migrations\resources\migration;

class ImportInitialPosts extends \li3_migrations\models\Migration {

	protected $_fields = array(
		'id' => array('type' => 'id'),
		'title' => array('type' => 'string'),
		'body' => array('type' => 'text')
	);

	protected $_records = array(
		array('title' => 'First post', 'body' => 'First post body text!'),
		array('title' => 'Second post', 'body' => 'Second post body text!'),
		array('title' => 'Third post', 'body' => 'Third post body text!')
	);

	protected $_source = 'migration_test';

	public function up() {
		return $this->save();
	}

	public function down() {
		return $this->truncate();
	}

}

?>