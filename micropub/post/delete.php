<?php

if (!defined('MICROLIGHT')) die();

function post_delete_post ($slug) {
	$db = new DB();
	$post = new Post($db);

	$where = [
		SQL::where_create('slug', $slug, SQLOP::EQUAL, SQLEscape::SLUG),
		SQL::where_create('status', 'deleted', SQLOP::NEQUAL, SQLEscape::POST_TYPE),
	];

	$existing_post = $post->find_one($where);

	// Check if the post exists before trying to delete it
	if ($existing_post === null) throw new Exception('Post does not exist');

	$properties = [
		'status' => 'deleted',
	];

	try {
		$post->update($properties, $where);
	} catch (\Throwable $error) {
		ml_http_error(HTTPStatus::SERVER_ERROR, $error->getMessage());
		return;
	}

	if (should_perform_webmention($existing_post) === true) {
		try {
			ml_webmention_perform($existing_post['url'], $existing_post['slug']);
		} catch (\Throwable $error) {
			// This error is not critical, as such, so a failing webmention does
			// not really warrant it to be handled as such, hence the simple
			// error logging.
			error_log('Could not perform webmention. Here is why:');
			error_log('Code: ' . $error->getCode());
			error_log('Message: ' . $error->getMessage());
		}
	}
}
