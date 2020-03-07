<?php

define('MICROLIGHT', 'v1.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/api.include.php');

if (ml_api_method() !== HTTPMethod::POST) {
	header('Allow: POST');
	ml_http_response(HTTPStatus::METHOD_NOT_ALLOWED);
	return;
}

// Initialise POST
if (!ml_api_post_decode()) return;

require_once('functions.php');

try {
	// Step 1: Validate input, can't proceed if the URLs aren't set up properly!
	$post_validation = ml_webmention_validate_post();
	if ($post_validation !== true) throw new Exception($post_validation);

	// Easier variable names to type :D
	$source = $post['source'];
	$target = $post['target'];

	$db = new DB();

	// Step 2: Make sure the post actually exists before adding a comment to it
	$post_id = ml_webmention_validate_target($db, $target);

	// Step 3: Fetch source contents to insert into database
	$post_details = ml_webmention_validate_source_contents($source, $target);

	// Step 4: See if the post's author is already in the database, adding
	//         them if not.
	$author = ml_webmention_validate_author($db, $post_details['author']);

	// Step 5: Finally, store the webmention in the database, updating or
	//         deleting the interaction if it has changed.
	$interaction_id = ml_webmention_interaction_store(
		$db,
		$source,
		$post_id,
		$post_details,
		$author
	);

	ml_http_response(HTTPStatus::OK);
	return;
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
