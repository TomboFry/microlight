<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');

// Redirect to homepage if we're trying to load it in the browser
$method = $_SERVER['REQUEST_METHOD'];
$content_type = $_SERVER['CONTENT_TYPE'];
if (empty($content_type)) $content_type = $_SERVER['HTTP_CONTENT_TYPE'];

if ($content_type === 'application/json') {
	// TODO: Process JSON requests as well.
	// This has been disabled temporarily because all JSON requests require
	// items to be contained within an array.
	ml_http_error(HTTPStatus::INVALID_REQUEST, 'JSON not supported');
	return;
	// $post = json_decode(file_get_contents('php://input'), true);
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

require_once('get.php');
require_once('post.php');
require_once('auth.php');

$bearer = get_access_token();

// If there is no token, end processing early with a warning.
if (empty($bearer)) {
	ml_http_error(HTTPStatus::UNAUTHORIZED, 'Bearer token has not been provided');
	return;
}

if (validate_token($bearer) === false) {
	ml_http_error(HTTPStatus::FORBIDDEN, 'Bearer token is invalid or does not exist');
	return;
}

switch ($method) {
case 'GET':
	switch (get('q')) {
	case 'config':
		ml_http_response(HTTPStatus::OK, query_config());
		return;
	case 'syndicate-to':
		ml_http_response(HTTPStatus::OK, query_syndicate_to());
		return;
	}
	ml_http_response(HTTPStatus::REDIRECT, null, null, ml_base_url());
	return;
case 'POST':
	switch (post('h')) {
	case 'entry':
		post_create_entry();
		return;
	}
	ml_http_response(HTTPStatus::REDIRECT, null, null, ml_base_url());
	return;
}
