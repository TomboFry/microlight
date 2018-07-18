<?php

if (!defined('MICROLIGHT_INIT')) die();

require('sql.include.php');

class DBError extends Exception {}

class DB {
	public $db;
	public $sql;

	function __construct () {
		$this->db = new PDO('sqlite:' . Config::DB_FILE);
		$this->sql = new SQL($this->db);
	}

	public function close() {
		$this->db = null;
	}
}

class Model {
	private $table_name = ''; // Inherited classes must set this.
	private $findAllStatement;
	private $db;

	function __construct(&$db, $table_name) {
		$this->db = $db->db;
		$this->table_name = $table_name;

		// Create the various statements
		$this->findAllStatement = $this->db->prepare("SELECT * FROM $table_name LIMIT :limit OFFSET :offset");
		if ($this->findAllStatement === false) {
			throw new DBError("Table \"$table_name\" not set up - TODO: Redirect to install.php", 0);
		}
	}

	function findAll ($limit = -1, $offset = 0) {
		$this->findAllStatement->bindParam(':limit', $limit);
		$this->findAllStatement->bindParam(':offset', $offset);
		$this->findAllStatement->execute();
		return $this->findAllStatement->fetchAll();
	}

	function findOne ($offset = 0) {
		$limit = 1;
		$this->findAllStatement->bindParam(':limit', $limit);
		$this->findAllStatement->bindParam(':offset', $offset);
		$this->findAllStatement->execute();
		return $this->findAllStatement->fetch();
	}
}

class Identity extends Model {
	public $table_name = 'identity';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);

		// Create the table if it does not already exist

		// We should assume that this table already exists, and should
		// therefore not be run every time the blog is loaded.
		/*
		$db->db->exec($db->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => 'INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE'
			],
			[
				'column' => 'name',
				'type' => 'TEXT NOT NULL'
			],
			[
				'column' => 'email',
				'type' => 'TEXT'
			],
			[
				'column' => 'note',
				'type' => 'TEXT'
			]
		]));
		*/
	}
}

class RelMe extends Model {
	public $table_name = 'relme';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);

		// We should assume that this table already exists, and should
		// therefore not be run every time the blog is loaded.
		/*
		$db->db->exec($db->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => 'INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE'
			],
			[
				'column' => 'name',
				'type' => 'TEXT'
			],
			[
				'column' => 'url',
				'type' => 'TEXT NOT NULL'
			]
		], [
			[
				'table' => 'identity',
				'reference' => 'id'
			]
		]));
		*/
	}
}

class Post extends Model {
	public $table_name = 'post';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);

		/*
		$db->db->exec($db->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => 'INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE'
			],
			[
				'column' => 'name',
				'type' => 'TEXT'
			],
			[
				'column' => 'content',
				'type' => 'TEXT NOT NULL'
			],
			[
				'column' => 'type',
				'type' => 'TEXT NOT NULL'
			],
			[
				'column' => 'slug',
				'type' => 'TEXT NOT NULL'
			],
			[
				'column' => 'published',
				'type' => 'TEXT NOT NULL'
			],
			[
				'column' => 'location',
				'type' => 'TEXT'
			],
			[
				'column' => 'url',
				'type' => 'TEXT'
			]
		], [
			[
				'table' => 'identity',
				'reference' => 'id'
			]
		]));
		*/
	}
}

class PostTag extends Model {
	public $table_name = 'tag';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);

		/*
		$db->db->exec($db->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => 'INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE'
			],
			[
				'column' => 'tag',
				'type' => 'TEXT'
			]
		], [
			[
				'table' => 'post',
				'reference' => 'id'
			]
		]));
		*/
	}
}
