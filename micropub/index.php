<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');

// Redirect to homepage if we're trying to load it in the browser
$method = $_SERVER['REQUEST_METHOD'];
$content_type = $_SERVER['CONTENT_TYPE'];

if ($content_type === 'application/json') {
	$post = json_decode(file_get_contents('php://input'), true);
} else {
	$post = $_POST;
}

function post ($key) {
	global $post;

	if (isset($post[$key]) && !empty($post[$key])) return $post[$key];
	return null;
}

function get ($key) {
	if (isset($_GET[$key]) && !empty($_GET[$key])) return $_GET[$key];
	return null;
}

// https://stackoverflow.com/a/2955878
function slugify ($text) {
	// replace non letter or digits by -
	$text = preg_replace('~[^\pL\d]+~u', '-', $text);

	// transliterate
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	// trim
	$text = trim($text, '-');

	// remove duplicate -
	$text = preg_replace('~-+~', '-', $text);

	// lowercase
	$text = strtolower($text);

	return $text;
}

require_once('get.php');
require_once('post.php');

if ($method === 'GET') {
	if (get('q') === 'config') {
		ml_http_response(HTTPStatus::OK, query_config());
	} else if (get('q') === 'syndicate-to') {
		ml_http_response(HTTPStatus::OK, query_syndicate_to());
	} else {
		ml_http_response(HTTPStatus::REDIRECT, null, null, ml_base_url());
	}
} else if ($method === 'POST') {
	process_request();
}
