<?php

namespace li3_migrations\tests\cases\extensions\command;

use li3_migrations\extensions\command\Migrate;
use li3_migrations\tests\mocks\extensions\command\MockMigrate;
use li3_migrations\tests\mocks\models\Migration as MockTable;
use lithium\console\Request;
use lithium\core\Libraries;
use lithium\test\Unit;

class MigrateTest extends Unit {

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

		foreach(glob(dirname($this->_migrationPath) . '/test_migration/*') as $path) {
			$name = basename($path);
			copy($path, $this->_migrationPath . '/' . $name);
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

	public function testPrepareTimestamp() {
		$this->request->params += array(
			'command' => 'migrate'
		);
		$model = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 10000000000000;
		$result = $model->invokeMethod('_prepareTimestamp', array('1'));
		$this->assertEqual($expected, $result);

		$expected = 20130000000000;
		$result = $model->invokeMethod('_prepareTimestamp', array('2013'));
		$this->assertEqual($expected, $result);

		$pattern = '/[1-9][0-9]{13}/';
		$result = $model->invokeMethod('_prepareTimestamp', array(null));
		$this->assertTrue(!!preg_match($pattern, $result));
	}

	public function testState() {
		$this->request->params += array(
			'command' => 'migrate'
		);
		$model = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = '10000000000000';
		$result = $model->invokeMethod('_getState');
		$this->assertEqual($expected, $result);

		$expected = '20130507103233';
		$model->invokeMethod('_setState', array($expected));
		$result = $model->invokeMethod('_getState');
		$this->assertEqual($expected, $result);
	}

	public function testGetMigrations() {
		$this->request->params += array(
			'command' => 'migrate'
		);
		$model = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$path = Libraries::get('li3_migrations', 'path') . '/resources/migration';

		$expected = array(
			array(
				'timestamp' => 20130506221317,
				'class' => '\li3_migrations\resources\migration\CreatePostsTable',
				'file' => $path . '/20130506221317_CreatePostsTable.php'
			),
			array(
				'timestamp' => 20130506221429,
				'class' => '\li3_migrations\resources\migration\ImportInitialPosts',
				'file' => $path . '/20130506221429_ImportInitialPosts.php'
			)
		);
		$result = $model->invokeMethod('_getMigrations', array($path));
		$this->assertEqual($expected, $result);
	}

	public function testFilterMigrationsUp() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'up'
		);
		$model = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$path = Libraries::get('li3_migrations', 'path') . '/resources/migration';

		$result = $model->invokeMethod('_filterMigrations', array(20130506221317));
		$expected = array(
			array (
				'timestamp' => 20130506221317,
				'class' => '\li3_migrations\resources\migration\CreatePostsTable',
				'file' => $path . '/20130506221317_CreatePostsTable.php'
			)
		);
		$this->assertEqual($expected, $result);

		$result = $model->invokeMethod('_filterMigrations', array(20130506221429));
		$expected = array(
			array (
				'timestamp' => 20130506221317,
				'class' => '\li3_migrations\resources\migration\CreatePostsTable',
				'file' => $path . '/20130506221317_CreatePostsTable.php'
			),
			array (
				'timestamp' => 20130506221429,
				'class' => '\li3_migrations\resources\migration\ImportInitialPosts',
				'file' => $path . '/20130506221429_ImportInitialPosts.php'
			)
		);
		$this->assertEqual($expected, $result);
	}

	public function testFilterMigrationsDown() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'down'
		);
		$model = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$model->invokeMethod('_setState', array('20130506221429'));
		$path = Libraries::get('li3_migrations', 'path') . '/resources/migration';

		$result = $model->invokeMethod('_filterMigrations', array(20130506221429));
		$expected = array(
			1 => array (
				'timestamp' => 20130506221429,
				'class' => '\li3_migrations\resources\migration\ImportInitialPosts',
				'file' => $path . '/20130506221429_ImportInitialPosts.php',
				'index' => 1
			)
		);
		$this->assertEqual($expected, $result);

		$result = $model->invokeMethod('_filterMigrations', array(20130506221317));
		$expected = array(
			array (
				'timestamp' => 20130506221317,
				'class' => '\li3_migrations\resources\migration\CreatePostsTable',
				'file' => $path . '/20130506221317_CreatePostsTable.php',
				'index' => 0
			),
			array (
				'timestamp' => 20130506221429,
				'class' => '\li3_migrations\resources\migration\ImportInitialPosts',
				'file' => $path . '/20130506221429_ImportInitialPosts.php',
				'index' => 1
			)
		);
		$this->assertEqual($expected, $result);
	}

	public function testShowState() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'show-state'
		);
		$migrate = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$migrate->showState();

		$expected = 'State of `li3_migrations` library migrations: 10000000000000' . "\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testShowAvailable() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'show-available'
		);
		$migrate = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$migrate->showAvailable();

		$expected  = "--------------------------------------------------------------------------------\n";
		$expected .= "Timestamp     \tClass                                                           \t\n";
		$expected .= "--------------\t----------------------------------------------------------------\t\n";
		$expected .= "20130506221317\t\\li3_migrations\\resources\\migration\\CreatePostsTable  \t\n";
		$expected .= "20130506221429\t\\li3_migrations\\resources\\migration\\ImportInitialPosts\t\n";

		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testCustomConnection() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'up', 'connection' => 'test_connection'
		);
		$migrate = new MockMigrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));

		$expected = 'test_connection';
		$result = $migrate->getOptions('connection');
		$this->assertEqual($expected, $result);
	}

	public function testMigrateUpDown() {
		$this->request->params += array(
			'command' => 'migrate', 'action' => 'up'
		);
		$migrate = new Migrate(array(
			'request' => $this->request, 'classes' => $this->classes
		));


		$migrate->up(20130506221317);
		$expected = array();
		$result = MockTable::all()->to('array');
		$this->assertEqual($expected, $result);

		$expected = "Success `Migration::up` timestamp: `20130506221317`\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';

		$migrate->up(20130506221429);
		$expected = array(
			1 => array (
				'id' => '1',
				'title' => 'First post',
				'body' => 'First post body text!',
			),
			2 => array (
				'id' => '2',
				'title' => 'Second post',
				'body' => 'Second post body text!',
			),
			3 => array (
				'id' => '3',
				'title' => 'Third post',
				'body' => 'Third post body text!',
			)
		);
		$result = MockTable::all()->to('array');
		$this->assertEqual($expected, $result);

		$expected = "Success `Migration::up` timestamp: `20130506221429`\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';

		$migrate->request->params['action'] = 'down';

		$migrate->down(20130506221429);
		$expected = array();
		$result = MockTable::all()->to('array');
		$this->assertEqual($expected, $result);

		$expected = "Success `Migration::down` timestamp: `20130506221429`\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';

		$migrate->down(20130506221317);

		$expected = "Success `Migration::down` timestamp: `20130506221317`\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';

		$migrate->request->params['action'] = 'up';
		$migrate->up();
		$expected = array(
			1 => array (
				'id' => '1',
				'title' => 'First post',
				'body' => 'First post body text!',
			),
			2 => array (
				'id' => '2',
				'title' => 'Second post',
				'body' => 'Second post body text!',
			),
			3 => array (
				'id' => '3',
				'title' => 'Third post',
				'body' => 'Third post body text!',
			)
		);
		$result = MockTable::all()->to('array');
		$this->assertEqual($expected, $result);

		$expected  = "Success `Migration::up` timestamp: `20130506221317`\n";
		$expected .= "Success `Migration::up` timestamp: `20130506221429`\n";
		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';

		$migrate->request->params['action'] = 'down';
		$migrate->down(1);
		$expected  = "Success `Migration::down` timestamp: `20130506221429`\n";
		$expected .= "Success `Migration::down` timestamp: `20130506221317`\n";

		$result = $migrate->response->output;
		$this->assertEqual($expected, $result);

		$migrate->response->output = '';
	}

}

?>