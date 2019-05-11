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
	// Permalink structure. Ideally, should not be hardcoded here.
	$url_prefix = ml_base_url() . '?post_slug=';
	$url = ml_api_get('url');

	$pos = strpos($url, $url_prefix);
	if ($pos === false) throw new Exception('Invalid post URL');

	// Determine the slug based on the provided URL
	$slug = substr($url, $pos + strlen($url_prefix));

	$db = new DB();
	$post = new Post($db);
	
	$where = [
		// ALWAYS ONLY show public posts
		[
			'column' => 'public',
			'operator' => SQLOP::EQUAL,
			'value' => 1,
			'escape' => SQLEscape::NONE,
		],
		[
			'column' => 'slug',
			'operator' => SQLOP::EQUAL,
			'value' => $slug,
			'escape' => SQLEscape::SLUG,
		],
	];

	$single = $post->find_one($where);

	if ($single === null) {
		throw new Exception('Post does not exist');
	}

	return Post::to_microformats($single);
}
