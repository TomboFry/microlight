<?php

if (!defined('MICROLIGHT')) die();

require_once('lib/enum.php');

abstract class ResponseCode extends BasicEnum {
	// Success
	const OK = ['code' => 200, 'description' => 'OK'];
	const CREATED = ['code' => 201, 'description' => 'Created'];
	const NO_CONTENT = ['code' => 204, 'description' => 'No Content'];
	// Errors
	const FORBIDDEN = ['code' => 403, 'description' => 'forbidden'];
	const UNAUTHORIZED = ['code' => 401, 'description' => 'unauthorized'];
	const INSUFFICIENT_SCOPE = ['code' => 401, 'description' => 'insufficient_scope'];
	const INVALID_REQUEST = ['code' => 400, 'description' => 'invalid_request'];
	const SERVER_ERROR = ['code' => 500, 'description' => 'server_error'];
}

abstract class HTTPMethod extends BasicEnum {
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const PATCH = 'PATCH';
	const DELETE = 'DELETE';
	const OPTIONS = 'OPTIONS';
}

function response ($response_code = ResponseCode::SERVER_ERROR, $location, $contents) {
	header('HTTP/1.1 ' . $response_code['code']);

	if (!empty($location)) {
		header('Location: ' . $location);
		return;
	}

	if (!empty($contents)) {
		header('Content-Type: application/json');
		echo $contents;
	}

	return;
}

function show_error ($error = ResponseCode::SERVER_ERROR, $description = '') {
	if (!ResponseCode::isValidValue($error)) {
		$error = ResponseCode::SERVER_ERROR;
		$description = 'ResponseCode enum incorrect';
	}

	return response(
		$error,
		null,
		json_encode([
			'error' => $error['description'],
			'error_description' => $description,
		])
	);
}

function request ($url, $method = HTTPMethod::GET, $body = null) {
	if ($url === null || $url === '') throw Exception('Provide URL');
	if (!HTTPMethod::isValidValue($method)) throw Exception('Provide correct method');
	if ($method === HTTPMethod::GET && $body !== null) throw Exception('Cannot send body in GET request');

	$curl = curl_init();

	$settings = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => $url,
	];

	if ($body !== null) {
		$settings[CURLOPT_POSTFIELDS] = http_build_query($body);
	}
	if ($method === HTTPMethod::POST) {
		$settings[CURLOPT_POST] = true;
	}
	if ($method !== HTTPMethod::GET && $method !== HTTPMethod::POST) {
		$settings[CURLOPT_CUSTOMREQUEST] = $method;
	}

	curl_setopt_array($curl, $settings);

	$result = curl_exec($curl);
	$errors = curl_error($curl);
	curl_close($curl);

	if ($result === false || $errors !== '') {
		return $errors;
	}

	return $result;
}
