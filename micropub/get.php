<?php

if (!defined('MICROLIGHT')) die();

// See: https://www.w3.org/TR/micropub/#syndication-targets
function syndicate_to () {
	return [];
}

/**
 * Get details about syndication target  
 * URL: `micropub?q=syndicate-to`
 * @return array
 */
function query_syndicate_to () {
	return [
		'syndicate-to' => syndicate_to(),
	];
}

/**
 * Get details about the server, including its media endpoint and
 * syndication targets.  
 * URL: `micropub?q=config`
 * @return array
 */
function query_config () {
	return [
		'media-endpoint' => ml_base_url() . 'media/index.php',
		'syndicate-to' => syndicate_to(),
	];
}

/**
 * Get all details about a specific post  
 * URL: `micropub?q=source&url=...`
 * @return array
 */
function query_source () {
	$url = ml_api_get('url');
	$slug = ml_slug_from_url($url);

	$db = new DB();
	$post = new Post($db);
	
	$where = [
		// ALWAYS ONLY show public posts
		SQL::where_create('status', 'public'),
		SQL::where_create('slug', $slug, SQLOP::EQUAL, SQLEscape::SLUG),
	];

	$single = $post->find_one($where);

	if ($single === null) {
		throw new Exception('Post does not exist');
	}

	// Get post details in microformat syntax
	$details = Post::to_microformats($single);

	// Only display properties requested
	$properties = ml_api_get('properties');
	if ($properties !== null && is_array($properties) && !empty($properties)) {
		// Type is not required when requesting properties
		unset($details['type']);

		// Delete any properties that aren't those requested
		foreach ($details['properties'] as $key => $value) {
			if (!in_array($key, $properties, true)) {
				unset($details['properties'][$key]);
			}
		}
	}

	return $details;
}
