<?php

if (!defined('MICROLIGHT')) die();

require_once('sql.include.php');

class DBError extends Exception {
}

class DB {
	/**
	 * @var PDO $pdo
	 * @var SQL $sql
	 */
	public $db;
	public $sql;

	function __construct () {
		$this->db = new PDO('sqlite:' . Config::DB_FILE);
		$this->sql = new SQL($this->db);
	}

	public function close () {
		$this->db = null;
	}
}

class Model {
	/**
	 * @var string $table_name
	 * @var PDO &$db
	 * @var SQL $sql
	 */
	public $table_name = ''; // Inherited classes must set this.
	public $db;
	public $sql;

	/**
	 * Model constructor.
	 *
	 * @param PDO $db
	 * @param string $table_name
	 */
	function __construct (&$db, $table_name) {
		$this->db = $db->db;
		$this->sql = $db->sql;
		$this->table_name = $table_name;
	}

	/**
	 * To be overridden by inherited classes.
	 *
	 * @throws DBError
	 */
	function create_table () {
		throw new DBError('Cannot create an empty table', 1);
	}

	/**
	 * Main "SELECT"-like function, which fetches data from the DB
	 *
	 * @param array[] $where
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_field
	 * @param string $order_direction
	 * @return array
	 * @throws DBError
	 */
	function find ($where = [], $limit = -1, $offset = 0, $order_field = 'id', $order_direction = 'ASC') {
		$sql = "SELECT * FROM `$this->table_name`";
		$sql .= $this->sql->where($where);

		// Add ordering
		SQL::regex_test(SQLEscape::COLUMN, $order_field);
		SQL::regex_test(SQLEscape::ORDER_DIRECTION, $order_direction);
		$sql .= " ORDER BY `$order_field` $order_direction";

		// Add limiting (mostly used for pagination)
		$sql .= " LIMIT $limit OFFSET $offset";

		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		if ($stmt === false) throw new DBError('Could not execute query', 0);

		return $stmt->fetchAll();
	}

	/**
	 * The same as `find`, but only returns one result (or null)
	 *
	 * @param array $where
	 * @param int $offset
	 * @return array|null
	 * @throws DBError
	 */
	function find_one ($where = [], $offset = 0) {
		$results = $this->find($where, 1, $offset);
		if (count($results) > 0) return $results[0];
		return null;
	}

	/**
	 * Inserts a new row into the database model
	 *
	 * @param string[] $properties
	 * @return integer
	 */
	function insert ($properties) {
		$sql = 'INSERT INTO ' . $this->table_name . $this->sql->insert($properties);
		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		return $this->db->lastInsertId();
	}

	/**
	 * Returns the number of rows in a particular table, with optional
	 * filtering
	 *
	 * @param array[] $where
	 * @return int
	 */
	function count ($where = []) {
		$sql = "SELECT COUNT(id) as count FROM `$this->table_name`";
		$sql .= $this->sql->where($where);
		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		return (int)$stmt->fetch()->count;
	}
}

class Identity extends Model {
	public $table_name = 'identity';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function create_table () {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQLType::PRIMARY_KEY_TYPE,
			],
			[
				'column' => 'name',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				'column' => 'email',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				'column' => 'note',
				'type' => SQLType::TEXT_TYPE,
			],
		]));
	}
}

class RelMe extends Model {
	public $table_name = 'relme';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function create_table () {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQLType::PRIMARY_KEY_TYPE,
			],
			[
				'column' => 'name',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				'column' => 'url',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
		], [
			[
				'table' => 'identity',
				'reference' => 'id',
			],
		]));
	}
}

class Post extends Model {
	public $table_name = 'post';

	function __construct (&$db) {
		parent::__construct($db, $this->table_name);
	}

	function create_table () {
		// Create the table if it does not already exist
		$this->db->exec($this->sql->create($this->table_name, [
			[
				'column' => 'id',
				'type' => SQLType::PRIMARY_KEY_TYPE,
			],
			[
				// Post Title
				'column' => 'name',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				// Text based introduction to a particular post
				'column' => 'summary',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// Markdown post contents
				'column' => 'content',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// Post Type
				'column' => 'type',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// URL friendly copy of the title
				'column' => 'slug',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL . SQLType::MOD_UNIQUE,
			],
			[
				// Date/Time ISO8601
				'column' => 'published',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// Comma separated tags
				'column' => 'tags',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				// "lat,long", otherwise "Address"
				'column' => 'location',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				// If the post directly refers to a specific
				// location on the internet, here is where to
				// put it.
				'column' => 'url',
				'type' => SQLType::TEXT_TYPE,
			],
		], [
			// A post must be made by an identity, although there
			// should only ever be one identity.
			[
				'table' => 'identity',
				'reference' => 'id',
			],
		]));
	}

	function find ($where = [], $limit = -1, $offset = 0, $order_field = 'id', $order_direction = 'DESC') {
		$results = parent::find($where, $limit, $offset, $order_field, $order_direction);

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
