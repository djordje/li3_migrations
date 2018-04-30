<?php

namespace li3_migrations\extensions\command\create;

use lithium\util\Inflector;
use lithium\util\Text;

class Migration extends \lithium\console\command\Create {

	/**
	 * Get the namespace for the migration.
	 *
	 * @param string $request
	 * @param array|string $options
	 * @return string
	 */
	protected function _namespace($request, $options = array()) {
		return parent::_namespace($request, array('prepend' => 'resources.'));
	}

	/**
	 * Get the class name for the migration.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _class($request) {
		return Inflector::camelize($request->action);
	}

	/**
	 * Get DB table name for the migration
	 * Table name is pluralized (tableized) class name by default
	 *
	 * @param $request
	 * @return string
	 */
	protected function _source($request) {
		if ($request->source) return $request->source;
		return Inflector::tableize($request->action);
	}

	/**
	 * Save a template with the current params. Writes file to `Create::$path`.
	 * Override default save to add timestamp in file name.
	 *
	 * @param array $params
	 * @return string A result string on success of writing the file. If any errors occur along
	 *         the way such as missing information boolean false is returned.
	 */
	protected function _save(array $params = array()) {
		$defaults = array('namespace' => null, 'class' => null);
		$params += $defaults;

		if (empty($params['class']) || empty($this->_library['path'])) {
			return false;
		}
		$contents = $this->_template();
		$result = Text::insert($contents, $params);
		$namespace = str_replace($this->_library['prefix'], '\\', $params['namespace']);
		$date = date('YmdHis');
		$path = str_replace('\\', '/', "{$namespace}\\{$date}_{$params['class']}");
		$path = $this->_library['path'] . stristr($path, '/');
		$file = str_replace('//', '/', "{$path}.php");
		$directory = dirname($file);
		$relative = str_replace($this->_library['path'] . '/', "", $file);

		if ((!is_dir($directory)) && !mkdir($directory, 0755, true)) {
			return false;
		}
		if (file_exists($file)) {
			$prompt = "{$relative} already exists. Overwrite?";
			$choices = array('y', 'n');
			if ($this->in($prompt, compact('choices')) !== 'y') {
				return "{$params['class']} skipped.";
			}
		}

		if (file_put_contents($file, "<?php\n\n{$result}\n\n?>")) {
			return "{$params['class']} created in {$relative}.";
		}
		return false;
	}

}

?>