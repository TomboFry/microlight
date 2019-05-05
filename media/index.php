<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/lib/media.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== "POST") {
	ml_http_response(HTTPStatus::METHOD_NOT_ALLOWED);
	return;
}

try {
	if (!isset($_FILES['file'])) {
		throw new Exception('Provide an image named `file`');
	}

	$file = $_FILES['file'];
	if ($file['error'] > 0) {
		throw new UploadException($file);
	}

	$image = new ImageResizer($file);
	ml_http_response(HTTPStatus::CREATED, null, null, $image->get_url());
	return;
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
