<?php

namespace li3_migrations\tests\mocks\extensions\command;

use li3_migrations\extensions\command\Migrate;

class MockMigrate extends Migrate {

	public function getOptions($key = null) {
		if ($key && isset($this->_options[$key])) {
			return $this->_options[$key];
		}
		return $this->_options;
	}

}

?>