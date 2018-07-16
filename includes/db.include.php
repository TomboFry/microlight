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
	private $table_name = '';
	private $findAllStatement;
	private $db;

	function __construct(&$db, $table_name) {
		$this->db = $db->db;
		$this->table_name = $table_name;

		// Create the various statements
		$this->findAllStatement = $this->db->prepare("SELECT * FROM $table_name");
		if ($this->findAllStatement === false) {
			throw new DBError('Table not set up - TODO: Redirect to install.php');
		}
	}

	function findAll () {
		$this->findAllStatement->execute();
		return $this->findAllStatement->fetchAll();
	}

	function findOne () {
		$this->findAllStatement->execute();
		return $this->findAllStatement->fetch();
	}
}

class Identity extends Model {
	public $table_name = 'identity';
	public $name;
	public $email;
	public $note; // Tagline / description / short sentence about yourself.

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
				'type' => 'TEXT'
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
	public $name;
	public $url;

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);

		// We should assume that this table already exists, and should
		// therefore not be run every time the blog is loaded.
		/*
		$db->db->exec($db->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => 'INTEGER'
			],
			[
				'column' => 'name',
				'type' => 'TEXT'
			],
			[
				'column' => 'url',
				'type' => 'TEXT'
			],
			[
				'column' => 'identity_id',
				'type' => 'INTEGER'
			]
		], [
			[
				'column' => 'identity_id',
				'table' => 'identity',
				'reference' => 'id'
			]
		]));
		*/
	}
}
