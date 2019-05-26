<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/api.include.php');

// Initialise POST
if (!ml_api_post_decode()) return;

require_once('get.php');
require_once('post/index.php');
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

		case 'source':
			ml_http_response(HTTPStatus::OK, query_source());
			return;
		}

	case 'POST':
		// Micropub can either have actions via the `action` value, or assume
		// an entity is being created via the `type` (if JSON) or `h` (if form
		// encoded) values. Yeah, gets a little inconvenient with all the
		// different edge cases.

		switch (ml_api_post('action')) {
		case 'delete':
			if (!in_array(TokenScope::DELETE, $auth['scope'], true)) {
				ml_http_error(
					HTTPStatus::INSUFFICIENT_SCOPE,
					'Token is missing `' . TokenScope::DELETE . '` scope'
				);
				return;
			}

			$url = ml_api_post('url');
			$slug = ml_slug_from_url($url);

			post_delete_post($slug);
			ml_http_response(HTTPStatus::NO_CONTENT);
			return;
			break;

		case 'update':
			if (!in_array(TokenScope::UPDATE, $auth['scope'], true)) {
				ml_http_error(
					HTTPStatus::INSUFFICIENT_SCOPE,
					'Token is missing `' . TokenScope::UPDATE . '` scope'
				);
				return;
			}
			$url = ml_api_post('url');
			$slug = ml_slug_from_url($url);
			$properties = [
				'add' => ml_api_post('add'),
				'replace' => ml_api_post('replace'),
				'delete' => ml_api_post('delete'),
			];

			post_update_entry($slug, $properties);
			ml_http_response(HTTPStatus::NO_CONTENT);
			return;
			break;
		}

		if (!in_array(TokenScope::CREATE, $auth['scope'], true)) {
			ml_http_error(
				HTTPStatus::INSUFFICIENT_SCOPE,
				'Token is missing `' . TokenScope::CREATE . '` scope'
			);
			return;
		}

		$is_json = ml_api_content_type() === 'application/json';

		$type = $is_json === true
			? ml_api_post_json($post, 'type', true)
			: ml_api_post('h');

		switch ($type) {
		case 'entry':
		case 'h-entry':
			$entry = new PostEntry();
			$entry->parse_post($is_json);
			post_create_entry($entry);
			return;
		}
	}
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}

ml_http_response(HTTPStatus::REDIRECT, null, null, ml_base_url());
return;
