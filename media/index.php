<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/lib/media.php');
require_once('includes/api.include.php');

if (ml_api_method() !== HTTPMethod::POST) {
	header('Allow: POST');
	ml_http_response(HTTPStatus::METHOD_NOT_ALLOWED);
	return;
}

// Initialise POST
if (!ml_api_post_decode()) return;

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

// Require `media` in scope
if (!in_array(TokenScope::MEDIA, $auth['scope'], true)) {
	ml_http_error(
		HTTPStatus::INSUFFICIENT_SCOPE,
		'Token is missing `' . TokenScope::MEDIA . '` scope'
	);
	return;
}

try {
	if (!isset($_FILES['file'])) {
		throw new Exception('Provide an image named `file`');
	}

	$image = new ImageResizer($_FILES['file']);
	ml_http_response(HTTPStatus::CREATED, null, null, $image->get_permalink());
	return;
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
