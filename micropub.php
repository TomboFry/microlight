<?php

define('MICROLIGHT', 'v0.0.1');

require_once('includes/config.php');
require_once('includes/network.include.php');

// Redirect to homepage if we're trying to load it in the browser
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
	header('HTTP/1.1 301 Redirect');
	header('Location: /');
	return;
}
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

function process_request () {
	$h = post('h');
	$name = post('name');
	$content = post('content');
	$summary = post('summary');
	$photo = post('photo');
	$url = post('url');
	$categories = post('category');

	// Variables not necessarily set by the POST data
	$type = 'article';
	$slug = $name;

	// h parameter is required
	if ($h === null) {
		show_error(ResponseCode::INVALID_REQUEST, 'Field \'h\' required');
		return;
	}

	// If a name is not provided, assume it's a note (for now)
	if ($name === null) $type = 'note';

	// Create a summary from the content, if one was not provided
	if ($summary === '' || $summary === null) {
		// Limit to 160 characters, add ellipsis if it's longer
		$summary = preg_replace('/^\#(.*)\R+/', '', $content);
		$summary = preg_split('/$\R?^/m', $summary)[0];
		$summary = substr($summary, 0, 157);
		if (strlen($summary) === 157) $summary .= '...';
	}

	// Use photo if file is not provided
	// TODO: Manage uploaded files with `multipart/form-data`, and set the
	// post type depending on the uploaded file's mime type (eg. `image/jpg`
	// or `video/mp4`)
	if ($photo !== '' && $photo !== null) {
		$type = 'photo';
	}

	// Calculate the slug
	if ($slug === '' || $slug === null) {
		$slug = slugify(implode('-', array_slice(preg_split('/\s/m', $summary), 0, 5)));
		$slug = date('omd') . '-' . $slug;
	} else {
		$slug = date('omd') . '-' . slugify($slug);
	}

	// Turn the provided categories into a string
	// TODO: Before this line, perform webmentions if a category is a URL
	if ($categories !== '' && $categories !== null) {
		$categories = implode(',', $categories) . ',';
	}

	$db = new DB();
	$post = new Post($db);
	$existing = $post->count([
		[
			'column' => 'slug',
			'operator' => SQLOP::EQUAL,
			'value' => $slug,
			'escape' => SQLEscape::SLUG,
		],
	]);

	if ($existing > 0) {
		show_error(ResponseCode::INVALID_REQUEST, "Post with slug '$slug' already exists");
		return;
	}

	$postId = $post->insert([
		'name' => $name,
		'summary' => $summary,
		'content' => $content,
		'type' => $type,
		'slug' => $slug,
		'published' => date('c'),
		'tags' => $categories,
		'url' => $photo,
		'identity_id' => 1,
	]);

	$postId = intval($postId);

	if (is_int($postId) && $postId !== 0) {
		response(ResponseCode::CREATED, ml_post_permalink($slug), null);
		return;
	} else {
		show_error(ResponseCode::SERVER_ERROR, 'Could not create entry. Unknown reason.');
		return;
	}
}

process_request();
