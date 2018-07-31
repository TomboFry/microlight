<?php

define('MICROLIGHT', 'v0.0.1');

require_once('includes/lib/enum.php');

abstract class ResponseCode extends BasicEnum {
	// Success
	const OK = [ 'code' => 200, 'description' => 'OK' ];
	const CREATED = [ 'code' => 201, 'description' => 'Created' ];
	const NO_CONTENT = [ 'code' => 204, 'description' => 'No Content' ];

	// Errors
	const FORBIDDEN = [ 'code' => 403, 'description' => 'forbidden' ];
	const UNAUTHORIZED = [ 'code' => 401, 'description' => 'unauthorized' ];
	const INSUFFICIENT_SCOPE = [ 'code' => 401, 'description' => 'insufficient_scope' ];
	const INVALID_REQUEST = [ 'code' => 400, 'description' => 'invalid_request' ];
	const SERVER_ERROR = [ 'code' => 500, 'description' => 'server_error' ];
}

$method = $_SERVER['REQUEST_METHOD'];
$content_type = $_SERVER['CONTENT_TYPE'];

// Redirect to homepage if we're trying to load it in the browser
if ($method !== 'POST') header('Location: /');

if ($content_type === 'application/json') {
	$post = json_decode(file_get_contents('php://input'), true);
} else {
	$post = $_POST;
}

function post($key) {
	global $post;

	if (isset($post[$key]) && !empty($post[$key])) return $post[$key];
	return false;
}

function showError($error = ResponseCode::SERVER_ERROR, $description = '') {
	if (!ResponseCode::isValidValue($error)) {
		$error = ResponseCode::SERVER_ERROR;
		$description = 'ResponseCode enum incorrect';
	}

	header('Content-Type: application/json');
	header('HTTP/1.1 ' . $error['code']);
	echo json_encode([
		'error' => $error['description'],
		'error_description' => $description
	]);
	return;
}

// https://stackoverflow.com/a/2955878
function slugify($text) {
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

function processRequest () {
	$h = post('h');
	$name = post('name');
	$content = post('content');
	$summary = post('summary');
	$photo = post('photo');
	$url = post('url');
	$categories = post('category');

	// Variables not necessarily set by the POST data
	$type = 'article';
	$slug = '';
	
	// h parameter is required
	if ($h === false) return showError(ErrorCodes::INVALID_REQUEST, 'Field \'h\' required');

	// If a name is not provided, assume it's a note (for now)
	if ($name === false) $type = 'note';

	// Create a summary from the content, if one was not provided
	if ($summary === '' || $summary === false) {
		// Limit to 160 characters, add ellipsis if it's longer
		$summary = preg_replace('/^\#(.*)\R+/', '', $content);
		$summary = preg_split('/$\R?^/m', $summary)[0];
		$summary = substr($summary, 0, 157);
		if (strlen($summary) === 157) $summary .= '...';
	}
	
	if ($slug === '' || $slug === false) {
		$slug = slugify(implode('-', array_slice(preg_split('/\s/m', $summary), 0, 5)));
		$slug = date('omd') . '-' . $slug;
	} else {
		$slug = date('omd') . '-' . slugify($slug);
	}

	$categories = implode(',', $categories) . ',';

	header('Content-Type: application/json');
	echo json_encode([
		'files' => $_FILES,
		'h' => $h,
		'name' => $name,
		'summary' => $summary,
		'content' => $content,
		'photo' => $photo,
		'url' => $url,
		'categories' => $categories,
		'type' => $type,
		'slug' => $slug,
	]);
}

processRequest();
