<?php

namespace li3_migrations\extensions\command;

use lithium\console\Command;
use lithium\console\command\Create;
use lithium\core\Libraries;

/**
 * Database migration tool
 */
class Migrate extends Command {

	/**
	 * Current library configuration
	 *
	 * @see `lithium\core\Libraries::get()`
	 * @var array
	 */
	protected $_library = array();

	/**
	 * Current library migration directory path
	 *
	 * @var string
	 */
	protected $_path;

	/**
	 * Store array of valid migration files in current library
	 *
	 * @var array
	 */
	protected $_migrations = array();

	/**
	 * Options array that will be passed to Migration constructor
	 *
	 * @var array
	 */
	protected $_options = array();

	protected function _init() {
		parent::_init();
		$this->_library = (isset($this->request->library)) ? $this->request->library : true;
		$this->_library = Libraries::get($this->_library);
		$this->_path = $this->_library['path'] . '/resources/migration';
		$this->_migrations = $this->_getMigrations($this->_path);
		if (isset($this->request->params['connection'])) {
			$this->_options['connection'] = $this->request->params['connection'];
		}
	}

	/**
	 * Migrate up to version
	 */
	public function up($timestamp = null) {
		$timestamp = $this->_prepareTimestamp($timestamp);

		foreach($this->_filterMigrations($timestamp) as $migration) {
			if (include_once $migration['file']) {
				$m = new $migration['class']($this->_options);
				if ($m->up()) {
					$this->_setState($migration['timestamp']);
					$this->out(
						"Success `Migration::up` timestamp: `{$migration['timestamp']}`"
					);
				} else {
                    $this->out(
						"Failed `Migration::up` timestamp: `{$migration['timestamp']}`"
					);
                }
			}
		}
	}

	/**
	 * Migrate down (revert back) to version, to revert all migrations provide `timestamp` 1
	 */
	public function down($timestamp = null) {
		$timestamp = $this->_prepareTimestamp($timestamp);

		foreach(array_reverse($this->_filterMigrations($timestamp)) as $migration) {
			if (include_once $migration['file']) {
				$m = new $migration['class']($this->_options);
				if ($m->down()) {
					$state = $this->_prepareTimestamp(1);
					if (isset($this->_migrations[$migration['index'] - 1])) {
						$state = $this->_migrations[$migration['index'] - 1]['timestamp'];
					}
					$this->_setState($state);
					$this->out(
						"Success `Migration::down` timestamp: `{$migration['timestamp']}`"
					);
				} else {
					$this->out(
						"Failed `Migration::down` timestamp: `{$migration['timestamp']}`"
					);                    
                }
			}
		}
	}

	/**
	 * Generate table with all available migrations for current library
	 * @return bool
	 */
	public function showAvailable() {
		$rows[] = array('Timestamp', 'Class');
		$rows[] = array(str_repeat('-', 14), str_repeat('-', 64));
		foreach($this->_migrations as $migration) {
			unset($migration['file']);
			$rows[] = $migration;
		}
		$this->hr();
		$this->columns($rows);
		return true;
	}

	/**
	 * Show timestamp of latest applied migration
	 */
	public function showState() {
		$message  = 'State of `' . $this->_library['name'] . '` library migrations: ';
		$message .= $this->_getState();

		$this->out($message);
	}

	protected function _filterMigrations($timestamp) {
		$state = $this->_getState();
		$direction = $this->request->action;
		$migrations = $this->_migrations;

		foreach($this->_migrations as $i => $migration) {
			if ($direction === 'up') {
				if ($migration['timestamp'] <= $timestamp && $migration['timestamp'] > $state) {
					continue;
				}
			}
			if ($direction === 'down') {
				if ($migration['timestamp'] >= $timestamp && $migration['timestamp'] <= $state) {
					$migrations[$i]['index'] = $i;
					continue;
				}
			}
			unset($migrations[$i]);
		}

		return $migrations;
	}

	/**
	 * @param $timestamp
	 * @return float
	 */
	protected function _prepareTimestamp($timestamp) {
		$timestamp = $timestamp ?: (double) date('YmdHis');
		if (strlen($timestamp) < 14) $timestamp = (double) str_pad($timestamp, 14, '0');
		if (strlen($timestamp) > 14) $timestamp = (double) substr($timestamp, 0, 14);
		return $timestamp;
	}

	protected function _getState() {
		if (file_exists($file = $this->_path . '/state')) {
			return (double) file_get_contents($file);
		} else {
			return 10000000000000;
		}
	}

	protected function _setState($state) {
		file_put_contents($this->_path . '/state', $state);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	protected function _getMigrations($path) {
		if (file_exists($path) && is_dir($path)) {
			$files = glob($path . '/' . str_repeat('[0-9]', 14) . '_*.php');
			if ($files) {
				$output = array();
				foreach($files as $file) {
					$namespace  =  '\\' . $this->_library['prefix'];
					$namespace .= str_replace('/', '\\', substr(
						dirname($file), strlen($this->_library['path']) +1)
					);
					$class = explode('_', basename($file, '.php'));
					$timestamp = (double) reset($class);
					$class = $namespace . '\\' . end($class);

					$output[] = compact('timestamp', 'class', 'file');
				}
				return $output;
			}
		}
		return array();
	}

}

?>