<?php

if (!defined('MICROLIGHT')) die();

require_once('includes/webmention.include.php');

/**
 * Update an existing post in the database based on a given slug.
 * @param string $original_slug
 * @param array $properties
 * @return void
 */
function post_update_entry (string $original_slug, array $properties) {
	global $post;

	$db = new DB();
	$db_post = new Post($db);
	
	// 1. Get original post
	$original_post = $db_post->find_one([
		SQL::where_create(
			'slug',
			$original_slug,
			SQLOP::EQUAL,
			SQLEscape::SLUG
		)
	]);
	
	if ($original_post === null) {
		ml_http_error(
			HTTPStatus::INVALID_REQUEST,
			'Post does not already exist'
		);
		return;
	}
	
	// 2. Convert into PostEntry
	$original_entry = new PostEntry();
	$original_entry->parse_entry($original_post);

	// Make sure we traverse an array regardless of whether a value was provided
	// for each field or not
	if (empty($properties['add'])) {
		$properties['add'] = [];
	} elseif (!is_array($properties['add'])) {
		throw new Exception('Property "add" must be an array');
	}
	if (empty($properties['replace'])) {
		$properties['replace'] = [];
	} elseif (!is_array($properties['replace'])) {
		throw new Exception('Property "replace" must be an array');
	}
	if (empty($properties['delete'])) {
		$properties['delete'] = [];
	} elseif (!is_array($properties['delete'])) {
		throw new Exception('Property "delete" must be an array');
	}
	
	// 3. Perform add/replace/removes on the original PostEntry
	foreach ($properties['add'] as $key => $values) {
		if (!property_exists($original_entry, $key) || !is_array($original_entry->$key)) {
			throw new Exception('Cannot add to the "' . $key . '" property');
		}

		// According to micropub spec, all values must be inside an array, even
		// if only one value is provided.
		if (!is_array($values)) {
			throw new Exception('Values for "' . $key . '" must be an array');
		}

		$original_entry->$key = array_merge($original_entry->$key, $values);
	}

	foreach ($properties['replace'] as $key => $values) {
		if (!property_exists($original_entry, $key)) {
			throw new Exception('Cannot replace values in the "' . $key . '" property');
		}

		// According to micropub spec, all values must be inside an array, even
		// if only one value is provided.
		if (!is_array($values)) {
			throw new Exception('Values for "' . $key . '" must be an array');
		}

		// Depending on whether the original field was an array or not (eg.
		// `name` is a string field), use the entire array, or just the first
		// value.
		if (is_array($original_entry->$key)) {
			$original_entry->$key = $values;
		} else {
			$original_entry->$key = $values[0];
		}
	}

	foreach ($properties['delete'] as $key => $values) {
		// If provided a list of fields to delete, check that here.
		if (is_numeric($key)) {
			if (!property_exists($original_entry, $values)) {
				throw new Exception('Cannot delete the "' . $values . '" property');
			}

			// Nullifying the value should behave in a particular way depending
			// on its type. For exaemple, we don't want to set a required string
			// field to `null`, it should be an empty string instead.
			switch(gettype($original_entry->$values)) {
			case 'string':
				$original_entry->$values = '';
				break;
			case 'array':
				$original_entry->$values = [];
				break;
			default:
				$original_entry->$values = null;
			}
			continue;
		}

		if (!property_exists($original_entry, $key) || !is_array($original_entry->$key)) {
			throw new Exception('Cannot delete from the "' . $key . '" property');
		}
		
		// Delete individual values from an array
		foreach ($values as $value) {
			// Go through every value provided and check that it already exists
			// in the array. If not, just ignore it.
			$index = array_search($value, $original_entry->$key, true);
			if ($index === false || $index === null) continue;

			// Remove the value from the array
			array_splice($original_entry->$key, $index, 1);
		}
	}

	// 4. Insert back into database
	$new_post = post_create_post($original_entry);

	try {
		$db_post->update(
			$new_post['properties'],
			[ SQL::where_create('slug', $original_slug, SQLOP::EQUAL, SQLEscape::SLUG) ]
		);
	} catch (\Throwable $error) {
		ml_http_error(HTTPStatus::SERVER_ERROR, $error->getMessage());
		return;
	}

	if ($new_post['perform_webmention'] === true) {
		try {
			ml_webmention_perform($new_post['properties']['url'], $new_post['properties']['slug']);
		} catch (\Throwable $error) {
			// This error is not critical, as such, so a failing webmention does
			// not really warrant it to be handled as such, hence the simple
			// error logging.
			error_log('Could not perform webmention. Here is why:');
			error_log('Code: ' . $error->getCode());
			error_log('Message: ' . $error->getMessage());
		}
	}

	return;
}
