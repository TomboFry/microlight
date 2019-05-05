<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/lib/api.php');

// Initialise POST
if (!ml_api_post()) return;

require_once('get.php');
require_once('post.php');
require_once('auth.php');

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
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
