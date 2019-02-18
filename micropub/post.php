<?php

if (!defined('MICROLIGHT')) die();

/**
 * The main Micropub API logic. This takes the POST request and processes all
 * its variables to create a post (or not, if malformed).
 *
 * @throws Exception
 */
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
		ml_http_error(HTTPStatus::INVALID_REQUEST, 'Field \'h\' required');
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
		// Take the first 10 words from the summary
		$slug = slugify(implode('-', array_slice(preg_split('/\s/m', $summary), 0, 10)));
	} else {
		// Alternatively, if the slug is already populated (take from
		// the post's name), slugify it.
		$slug = slugify($slug);
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
		ml_http_error(HTTPStatus::INVALID_REQUEST, "Post with slug '$slug' already exists");
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
		ml_http_response(HTTPStatus::CREATED, null, null, ml_post_permalink($slug));
		return;
	} else {
		ml_http_error(HTTPStatus::SERVER_ERROR, 'Could not create entry. Unknown reason.');
		return;
	}
}
