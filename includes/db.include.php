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
		$this->db = Config::USE_MYSQL == true
			? new PDO(
				sprintf('mysql:dbname=%s;host=%s', Config::DB_NAME, Config::MYSQL_HOSTNAME),
				Config::MYSQL_USERNAME, Config::MYSQL_PASSWORD
			)
			: new PDO('sqlite:' . Config::DB_NAME . '.db');
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
	function find ($where = [], $limit = NULL, $offset = 0, $order_field = 'id', $order_direction = 'ASC') {
		$sql = "SELECT * FROM `$this->table_name`";
		$sql .= $this->sql->where($where);

		// Add ordering
		SQL::regex_test(SQLEscape::COLUMN, $order_field);
		SQL::regex_test(SQLEscape::ORDER_DIRECTION, $order_direction);
		$sql .= " ORDER BY `$order_field` $order_direction";

		// Add limiting (mostly used for pagination)
		if ($limit != NULL) $sql .= " LIMIT $limit OFFSET $offset";

		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		if ($stmt === false) throw new DBError(implode('; ', $this->db->errorInfo()), 0);

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
	 * @throws DBError
	 */
	function insert ($properties) {
		$sql = 'INSERT INTO ' . $this->table_name . $this->sql->insert($properties);
		$stmt = $this->db->query($sql, PDO::FETCH_OBJ);
		if ($stmt === false) throw new DBError(implode('; ', $this->db->errorInfo()), 0);
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

	/**
	 * Delete from the database. If no `$where` value is provided, the
	 * request will fail. This is to prevent all data being deleted from the
	 * table.
	 * @param array[] $where
	 * @return bool Whether the deletion was successful or not
	 * @throws DBError
	 */
	function delete ($where = []) {
		if (empty($where)) throw new Exception('This will delete all records. Not proceeding.');

		$sql = "DELETE FROM `$this->table_name`";
		$sql .= $this->sql->where($where);
		$stmt = $this->db->query($sql);
		if ($stmt === false) throw new DBError(implode('; ', $this->db->errorInfo()), 0);
		$stmt->fetch();

		// If all goes well...
		return true;
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
				'column' => 'title',
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
				'column' => 'post_type',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// URL friendly copy of the title
				'column' => 'slug',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL . SQLType::MOD_UNIQUE,
			],
			[
				// To be shown to users
				'column' => 'public',
				'type' => SQLType::BOOL_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// Date/Time ISO8601
				'column' => 'published',
				'type' => SQLType::DATETIME_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// Date/Time ISO8601
				'column' => 'updated',
				'type' => SQLType::DATETIME_TYPE,
			],
			[
				// Comma separated tags
				'column' => 'tags',
				'type' => SQLType::TEXT_TYPE . SQLType::MOD_NOT_NULL,
			],
			[
				// "lat,long", otherwise "Address"
				'column' => 'location',
				'type' => SQLType::TEXT_TYPE,
			],
			[
				// If the post directly refers to a specific location on the
				// internet, here is where to put it.
				'column' => 'url',
				'type' => SQLType::TEXT_TYPE,
			],
		]));
	}

	function find ($where = [], $limit = -1, $offset = 0, $order_field = 'published', $order_direction = 'DESC') {
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

	/**
	 * Convert a post into the microformats2 structure
	 * @param Post $post
	 * @return array
	 */
	static function to_microformats ($post) {
		$body = [
			'type' => 'h-entry',
			'properties' => [
				'name' => [ $post->title ],
				'summary' => [ $post->summary ],
				'content' => [[
					'value' => strip_tags($post->content),
					'html' => $post->content,
				]],
				'category' => $post->tags,
				'published' => [ $post->published ],
			],
		];

		if ($post->updated !== null) {
			$body['properties']['updated'] = [ $post->updated ];
		}

		switch ($post->post_type) {
		case 'photo':
			$body['properties']['photo'] = [ $post->url ];
			break;
		case 'audio':
			$body['properties']['audio'] = [ $post->url ];
			break;
		case 'video':
			$body['properties']['video'] = [ $post->url ];
			break;
		case 'like':
			$body['properties']['like-of'] = [ $post->url ];
			break;
		case 'bookmark':
			$body['properties']['bookmark-of'] = [ $post->url ];
			break;
		case 'reply':
			$body['properties']['in-reply-to'] = [ $post->url ];
			break;
		case 'repost':
			$body['properties']['repost-of'] = [ $post->url ];
			break;
		}

		return $body;
	}
}
