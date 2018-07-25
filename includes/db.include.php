<?php

if (!defined('MICROLIGHT_INIT')) die();

require_once('sql.include.php');

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
	public $table_name = ''; // Inherited classes must set this.
	public $db;
	public $sql;

	function __construct(&$db, $table_name) {
		$this->db = $db->db;
		$this->sql = $db->sql;
		$this->table_name = $table_name;
	}

	// This should be run by the inherited classes
	function createTable () {
		throw new DBError('Cannot create an empty table', 1);
	}

	// Main "SELECT" function, to fetch data from the DB
	function find ($where = [], $limit = -1, $offset = 0, $orderField = 'id', $orderDirection = 'ASC') {
		$sql = "SELECT * FROM `$this->table_name`";
		$sql .= $this->sql->where($where);

		// Add ordering
		$sql .= " ORDER BY `$orderField` $orderDirection";

		// Add limiting (mostly used for pagination)
		$sql .= " LIMIT $limit OFFSET $offset";

		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		if ($stmt === false) throw new DBError('Could not execute query', 0);

		return $stmt->fetchAll();
	}

	// Essentially the same as the "find" function but will return a single
	// object, instead of an array.
	function findOne ($where = [], $offset = 0) {
		$results = $this->find($where, 1, $offset);
		if (count($results) > 0) return $results[0];
		return NULL;
	}

	function insert($properties) {
		$sql = 'INSERT INTO ' . $this->table_name . $this->sql->insert($properties);
		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		return $this->db->lastInsertId();
	}

	function count () {
		$sql = 'SELECT COUNT(id) as count FROM ' . $this->table_name;
		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		return (int)$stmt->fetch()->count;
	}
}

class Identity extends Model {
	public $table_name = 'identity';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function createTable() {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQL::PRIMARY_KEY_TYPE
			],
			[
				'column' => 'name',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				'column' => 'email',
				'type' => SQL::TEXT_TYPE
			],
			[
				'column' => 'note',
				'type' => SQL::TEXT_TYPE
			]
		]));
	}
}

class RelMe extends Model {
	public $table_name = 'relme';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function createTable() {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQL::PRIMARY_KEY_TYPE
			],
			[
				'column' => 'name',
				'type' => SQL::TEXT_TYPE
			],
			[
				'column' => 'url',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			]
		], [
			[
				'table' => 'identity',
				'reference' => 'id'
			]
		]));
	}
}

class Post extends Model {
	public $table_name = 'post';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function createTable() {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQL::PRIMARY_KEY_TYPE
			],
			[
				// Post Title
				'column' => 'name',
				'type' => SQL::TEXT_TYPE
			],
			[
				// Text based introduction to a particular post
				'column' => 'summary',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				// Markdown post contents
				'column' => 'content',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				// Post Type
				'column' => 'type',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				// URL friendly copy of the title
				'column' => 'slug',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				// Date/Time ISO8601
				'column' => 'published',
				'type' => SQL::TEXT_TYPE . SQL::NOT_NULL
			],
			[
				// Comma separated tags
				'column' => 'tags',
				'type' => SQL::TEXT_TYPE
			],
			[
				// "lat,long", otherwise "Address"
				'column' => 'location',
				'type' => SQL::TEXT_TYPE
			],
			[
				// If the post directly refers to a specific
				// location on the internet, here is where to
				// put it.
				'column' => 'url',
				'type' => SQL::TEXT_TYPE
			]
		], [
			// A post must be made by an identity, although there
			// should only ever be one identity.
			[
				'table' => 'identity',
				'reference' => 'id'
			]
		]));
	}

	function find ($where = [], $limit = -1, $offset = 0, $orderField = 'id', $orderDirection = 'DESC') {
		$results = parent::find($where, $limit, $offset, $orderField, $orderDirection);

		// Process each result
		foreach ($results as $key => $value) {
			// Split the commas in the tags into an array
			$results[$key]->tags = explode(',', $value->tags);

			// Remove the last element, which is always empty
			array_pop($results[$key]->tags);
		}

		return $results;
	}
}
