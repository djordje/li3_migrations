<?php

namespace li3_migrations\tests\cases\extensions\command\create;

use li3_migrations\extensions\command\create\Migration;
use lithium\console\Request;
use lithium\core\Libraries;
use lithium\test\Unit;

class MigrationTest extends Unit {

	public $request;

	protected $_backup = array();

	protected $_migrationPath = null;

	public function skip() {
		$this->_migrationPath = Libraries::get('li3_migrations', 'path') . '/resources/migration';
		$this->skipIf(
			!is_writable(dirname($this->_migrationPath)),
			"Path `{$this->_migrationPath}` is not writable."
		);
	}

	public function setUp() {
		$this->classes = array('response' => 'lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();

		if (!file_exists($this->_migrationPath)) {
			mkdir($this->_migrationPath);
		}

		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->request->params = array('library' => 'li3_migrations');
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanUp();

		$files = glob($this->_migrationPath . '/*');

		if($files) {
			foreach($files as $file) {
				unlink($file);
			}
		}
		if(file_exists($this->_migrationPath)) rmdir($this->_migrationPath);
	}

	public function testNamespace() {
		$this->request->params += array(
			'command' => 'migration', 'action' => 'Test'
		);
		$model = new Migration(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 'li3_migrations\resources\migration';
		$result = $model->invokeMethod('_namespace', array($this->request));
		$this->assertEqual($expected, $result);
	}

	public function testClass() {
		$this->request->params += array(
			'command' => 'migration', 'action' => 'test_users'
		);
		$model = new Migration(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 'TestUsers';
		$result = $model->invokeMethod('_class', array($this->request));
		$this->assertEqual($expected, $result);
	}

	public function testSource() {
		$this->request->params += array(
			'command' => 'migration', 'action' => 'User'
		);
		$model = new Migration(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 'users';
		$result = $model->invokeMethod('_source', array($this->request));
		$this->assertEqual($expected, $result);
	}

	public function testSourceParam() {
		$this->request->params += array(
			'command' => 'migration', 'action' => 'User', 'source' => 'UserProfile'
		);
		$model = new Migration(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 'UserProfile';
		$result = $model->invokeMethod('_source', array($this->request));
		$this->assertEqual($expected, $result);
	}

	public function testRun() {
		$this->request->params += array(
			'command' => 'create', 'action' => 'migration', 'args' => array('Posts')
		);
		$migration = new Migration(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$migration->run('migration');
		$glob = glob($this->_migrationPath . '/*_Posts.php');
		$expected = 'Posts created in resources/migration/' . basename(reset($glob)) . ".\n";
		$result = $migration->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<< 'test'
<?php

namespace li3_migrations\resources\migration;

class Posts extends \li3_migrations\models\Migration {

	protected $_fields = [];

	protected $_records = [];
    
	protected $_meta = [];

	protected $_source = 'posts';

	public function up() {}

	public function down() {}

}

?>
test;
		$result = file_get_contents(reset($glob));
		$this->assertEqual($expected, $result);
	}
	
}

?>