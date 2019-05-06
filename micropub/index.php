<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/api.include.php');

// Initialise POST
if (!ml_api_post_decode()) return;

require_once('get.php');
require_once('post.php');
require_once('PostEntry.php');

$bearer = ml_api_access_token();

// If there is no token, end processing early with a warning.
if (empty($bearer)) {
	ml_http_error(HTTPStatus::UNAUTHORIZED, 'Bearer token has not been provided');
	return;
}

if (ml_api_validate_token($bearer) === false) {
	ml_http_error(HTTPStatus::FORBIDDEN, 'Bearer token is invalid or does not exist');
	return;
}

try {
	switch (ml_api_method()) {
	case 'GET':
		switch (ml_api_get('q')) {
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
		$is_json = ml_api_content_type() === 'application/json';
		$entry = new PostEntry($is_json);
		post_create_entry($entry);

		return;
	}
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
