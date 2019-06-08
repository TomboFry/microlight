<?php

// SQL Helper Functions
// ----
// These functions will simply help create some SQL queries, like creating
// tables (eg. `CREATE TABLE IF NOT EXISTS ...`)
//
// **These functions should only be used internally and not rely on user input.**

if (!defined('MICROLIGHT')) die();

require_once('lib/enum.php');

// All ASCII characters except comma
const TAG_CHARS = '[\x00-\x7F]';

abstract class SQLOP extends BasicEnum {
	const EQUAL = '=';
	const NEQUAL = '!=';
	const LIKE = 'LIKE';
	const GT = '>';
	const GTE = '>=';
	const LT = '<';
	const LTE = '<=';
	const IN = 'IN';
}

abstract class SQLEscape extends BasicEnum {
	// Any characters allowed, no checking
	const NONE = '/.*/';

	// At least one alphabetic or underscore character
	const COLUMN = '/^[a-zA-Z_]+$/';

	// At least one alphanumeric, underscore, or hyphen character
	const SLUG = '/^[a-zA-Z0-9_\-]+$/';

	// Either a list or a singular of at least one alphanumeric, underscore,
	// hyphen, or space character, optionally surrounded by percent symbols
	const TAG = '/^('. TAG_CHARS. '+|%('. TAG_CHARS. '+,)+%|('. TAG_CHARS. '+,)+|^$)$/';

	// At least one alphabetic character
	const POST_TYPE = '/^[a-z]+$/';

	// All capitals, always starting with a letter, then optionally more
	// letters or spaces
	const DB_TYPE = '/^[A-Z][A-Z ]*$/';

	// Either `ASC` or `DESC`, and nothing else
	const ORDER_DIRECTION = '/(ASC|DESC)/';

	// Full ISO8601 timestamp
	// Required: Year, Month, Day, Hour, Minute, and Seconds
	// Optional: T, Z, milliseconds, and timezone
	const ISO8601 = '/^[0-9]{4}-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])[T ]([0-1][0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9])(\.[0-9]+)?)?(Z|[\+-]([0-1][0-9]|2[0-3]):?([0-5][0-9])?)?$/';
}

abstract class SQLType extends BasicEnum {
	// Actual SQLite Types
	const PRIMARY_KEY_TYPE = 'INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE';
	const TEXT_TYPE = 'TEXT';
	const DATETIME_TYPE = 'DATETIME';
	const INTEGER_TYPE = 'INTEGER';
	const BOOL_TYPE = 'BOOLEAN';

	// Modifiers
	const MOD_NOT_NULL = ' NOT NULL';
	const MOD_UNIQUE = ' UNIQUE';
}

class SQL {
	// Class variables and functions
	/** @var PDO $db */
	private $db;

	/**
	 * SQL constructor
	 *
	 * @param PDO &$db A reference to the database (not a copy)
	 */
	function __construct (&$db) {
		$this->db = $db;
	}

	/**
	 * Checks that the specified value matches
	 *
	 * @param string $regex
	 * @param string $test
	 * @throws Exception
	 */
	public static function regex_test ($regex, $test) {
		// If the regex doesn't match just throw an exception
		if (!preg_match($regex, $test)) throw new Exception('Value "' . $test . '" invalid', 1);
	}

	/**
	 * Helper for the generation of the WHERE clauses
	 * @param string $column
	 * @param mixed $value
	 * @param SQLOP $operator
	 * @param SQLEscape $escape
	 * @return array
	 */
	public static function where_create ($column, $value, $operator = SQLOP::EQUAL, $escape = SQLEscape::NONE) {
		return [
			'column' => $column,
			'operator' => $operator,
			'value' => $value,
			'escape' => $escape
		];
	}

	/**
	 * Convert an array of properties into an SQL formatted list
	 *
	 * @param array[] $properties
	 * @return string
	 */
	private function propsToString ($properties) {
		$acc = '';

		// `array_walk` loops over every property provided.
		array_walk($properties, function (array $property, int $index) use (&$acc) {
			$type = $property['type'];
			$column = $property['column'];

			// Make sure "type" only contains uppercase characters or a space
			SQL::regex_test(SQLEscape::DB_TYPE, $type);

			// Same again but the column name may have an underscore instead
			SQL::regex_test(SQLEscape::COLUMN, $column);

			// Don't put a comma on the first element
			if ($index !== 0) $acc .= ', ';

			// Append the column name and its type
			$acc .= "`$column` $type";
		});

		return $acc;
	}

	/**
	 * Create a string of SQL foreign keys based on given properties
	 *
	 * @param array[] $foreign_keys
	 * @return string
	 */
	private function foreignKeyToString ($foreign_keys) {
		$types = '';
		$keys = '';

		// `array_walk` loops over every property provided.
		array_walk($foreign_keys, function ($key_props) use (&$types, &$keys) {
			// The table to refer to
			$table = $key_props['table'];

			// The column name from the foreign table
			$reference = $key_props['reference'];

			// Check all three props
			SQL::regex_test(SQLEscape::COLUMN, $table);
			SQL::regex_test(SQLEscape::COLUMN, $reference);

			$column = $table . '_' . $reference;

			$types .= ", `$column` INTEGER NOT NULL";
			$keys .= ", FOREIGN KEY(`$column`) REFERENCES `$table`(`$reference`)";
		});

		return $types . $keys;
	}

	/**
	 * Generate an SQL query to create a table based on given properties
	 *
	 * @param string $table_name
	 * @param array[] $properties
	 * @param array[]|null $foreign_keys
	 * @return string
	 */
	public function create (string $table_name, array $properties, array $foreign_keys = null) {
		$new_props = $this->propsToString($properties);
		$full_string = "CREATE TABLE IF NOT EXISTS `$table_name` ($new_props";
		if ($foreign_keys != null) {
			$full_string .= $this->foreignKeyToString($foreign_keys);
		}
		$full_string .= ');';
		return $full_string;
	}

	/**
	 * Generate part of an SQL query to filter results using the `WHERE`
	 * clause.
	 *
	 * @param array[] $conditions
	 * @return string
	 */
	public function where (array $conditions) {
		$acc = '';

		array_walk($conditions, function (array $condition, int $index) use (&$acc) {
			// Get condition properties
			$column = $condition['column'];
			$operator = $condition['operator'];
			$value = $condition['value'];
			$escape = $condition['escape'];

			// Test the passed parameters
			if (!SQLOP::isValidValue($operator)) {
				throw new Exception("Operator \"$operator\" invalid");
			}
			if (!SQLEscape::isValidValue($escape)) {
				throw new Exception("Escape type \"$escape\" invalid");
			}
			SQL::regex_test(SQLEscape::COLUMN, $column);

			// If we are querying based on an array, perform the regex and quote
			// functions on each value inside the array instead of the whole
			// value.
			if (is_array($value)) {
				foreach ($value as $key => $subvalue) {
					SQL::regex_test($escape, $subvalue);
					$value[$key] = $this->db->quote($subvalue);
				}
			} else {
				SQL::regex_test($escape, $value);
				$value = $this->db->quote($value);
			}

			if ($index > 0) {
				$acc .= ' AND';
			} else {
				$acc .= ' WHERE';
			}

			if (is_array($value) === true) {
				$acc .= " `$column` $operator (" . implode(",", $value) . ")";
			} else {
				$acc .= " `$column` $operator $value";
			}
		});

		return $acc;
	}

	/**
	 * Determine which regex escape to perform on the value, depending on what
	 * the name of the column is.
	 * @param string $column
	 * @return SQLEscape
	 */
	private static function determine_escape (string $column) {
		$escape = SQLEscape::NONE;
		switch ($column) {

		// Alphabetical characters only
		case 'post_type':
			$escape = SQLEscape::POST_TYPE;
			break;

		// A list of all ASCII characters
		case 'tags':
			$escape = SQLEscape::TAG;
			break;

		// Alphanumerical characters
		case 'slug':
			$escape = SQLEscape::SLUG;
			break;
		
		// Alphabetical characters only
		case 'status':
			$escape = SQLEscape::POST_TYPE;
			break;

		// ISO8601 date/time format
		case 'published':
		case 'updated':
			$escape = SQLEscape::ISO8601;
			break;
		}

		return $escape;
	}

	/**
	 * Generates part of an SQL query for inserting values into a table
	 *
	 * @param string[] $properties
	 * @return string
	 * @throws Exception
	 */
	public function insert (array $properties) {
		$keys = '';
		$values = '';

		foreach ($properties as $key => $value) {
			// 1. Test the key (column name)
			SQL::regex_test(SQLEscape::COLUMN, $key);

			// 2. Determine if there is a specific column that needs
			//    testing
			$escape = SQL::determine_escape($key);
			SQL::regex_test($escape, $value);

			// 3. Add the key/value to the output!
			$keys .= '`' . $key . '`,';
			$values .= $this->db->quote($value) . ',';
		};

		return ' (' . substr($keys, 0, -1) . ') VALUES (' . substr($values, 0, -1) . ')';
	}

	public function update (array $properties) {
		if (empty($properties)) throw new Exception('At least one property should be provided');

		$output = '';

		foreach ($properties as $column => $value) {
			// 1. Determine regex escape for value based on column name
			$escape = SQL::determine_escape($column);

			// 2. Test column name and value
			SQL::regex_test(SQLEscape::COLUMN, $column);
			if ($value !== null) SQL::regex_test($escape, $value);

			// 3. If all goes well, add them to the output!
			$output .= '`' . $column . '`=';
			if ($value === null) {
				$output .= 'null,';
			} else {
				$output .= $this->db->quote($value) . ',';
			}
		}

		// Remove last character because it's a comma
		return substr($output, 0, -1);
	}
}
