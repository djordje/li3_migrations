<?php

namespace li3_migrations\resources\migration;

class CreatePostsTable extends \li3_migrations\models\Migration {

	protected $_fields = array(
		'id' => array('type' => 'id'),
		'title' => array('type' => 'string'),
		'body' => array('type' => 'text')
	);

	protected $_records = array();

	protected $_source = 'migration_test';

	public function up() {
		$this->create();
	}

	public function down() {
		$this->drop();
	}

}

?>