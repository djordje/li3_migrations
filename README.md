# Database migrations for Lithium framework [![Build Status](https://travis-ci.org/djordje/li3_migrations.png?branch=master)](https://travis-ci.org/djordje/li3_migrations)

---

## Dependencies

[li3_fixtures](https://github.com/UnionOfRAD/li3_fixtures) - [Installation instructions](https://github.com/UnionOfRAD/li3_fixtures#readme)

[lithium](https://github.com/UnionOfRAD/lithium) from `master` branch (after v0.11) with integrated `li3_sqltools`

## Usage

#### `create migration`

Create new migration file with `li3 create migration` command:

	li3 create migration Users
	//app/resources/migration/20130506002905_Users.php


```php

	namespace app\resources\migration;

	class Users extends \li3_migrations\models\Migration {

		protected $_fields = array();

		protected $_records = array();

		protected $_source = 'users';

		public function up() {}

		public function down() {}

	}

```

You can provide arguments to command:

* `source` - custom table name (this is value of `source` property): `--source=site_users`
* `library` - specify library to use: `--library=li3_usermanager`

#### `migrate`

Run migrations with `li3 migrate` command:

Available `li3 migrate` actions:

* `up` - accept timestamp param: `li3 migrate up` or `li3 migrate up 20130505` or  `li3 migrate up 20130505102033`
* `down` - accept timestamp param: `li3 migrate down 1` or `li3 migrate down 20130505` or  `li3 migrate down 20130505102033`
* `show-available` - generate table with all available migrations in current library
* `show-state` - show timestamp of latest applied migration