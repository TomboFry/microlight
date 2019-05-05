<?php

if (!defined('MICROLIGHT')) die();

// HTTP specific functions - making requests, parsing responses, etc.

require_once('enum.php');

abstract class HTTPStatus extends BasicEnum {
	// Success
	const OK = ['code' => 200, 'description' => 'OK'];
	const CREATED = ['code' => 201, 'description' => 'Created'];
	const NO_CONTENT = ['code' => 204, 'description' => 'No Content'];
	// Misc
	const REDIRECT = ['code' => 301, 'description' => 'Redirect'];
	const METHOD_NOT_ALLOWED = ['code' => 405, 'description' => 'Method Not Allowed'];
	// Errors (specific to Micropub)
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

abstract class HTTPContentType extends BasicEnum {
	const JSON = 'application/json';
	const FORM_DATA = 'application/x-www-form-urlencoded';
	const MULTIPART = 'multipart/form-data';
}

/**
 * Returns the HTTP request made with a response, setting the status code,
 * contents, and redirection, if any.
 *
 * @param array $status Uses a HTTPStatus enum value
 * @param array|null $contents
 * @param string $content_type Uses a HTTPContentType enum value
 * @param string|null $location Redirection location, if any
 * @throws Exception
 */
function ml_http_response (
	$status = HTTPStatus::SERVER_ERROR,
	$contents = null,
	$content_type = HTTPContentType::JSON,
	$location = null
) {
	if ($status !== null && !HTTPStatus::isValidValue($status)) {
		throw new Exception('Invalid status');
	}
	if ($content_type !== null && !HTTPContentType::isValidValue($content_type)) {
		throw new Exception('Invalid Content-Type');
	}
	header('HTTP/1.1 ' . $status['code']);

	if (!empty($location) && $location !== null) {
		header('Location: ' . $location);
		return;
	}

	if (!empty($contents) && $contents !== null) {
		header('Content-Type: ' . $content_type);
		switch ($content_type) {
			case HTTPContentType::JSON:
				echo json_encode($contents);
				break;
			case HTTPContentType::FORM_DATA:
			case HTTPContentType::MULTIPART:
				echo http_build_query($contents);
			default:
				echo $contents;
		}
	}
}

/**
 * Returns the HTTP request made with a standardised, formatted error payload.
 *
 * @param array $error Uses HTTPStatus enum value
 * @param string $description
 * @throws Exception
 */
function ml_http_error ($error = HTTPStatus::SERVER_ERROR, $description = '') {
	if (!HTTPStatus::isValidValue($error)) {
		$error = HTTPStatus::SERVER_ERROR;
		$description = 'ResponseCode enum incorrect';
	}

	ml_http_response(
		$error,
		['error' => $error['description'], 'error_description' => $description]
	);

	return;
}

/**
 * Decode a formdata encoded string into an array of values
 *
 * @param string $response Formdata encoded request data
 * @return array
 */
function ml_formdata_decode ($response) {
	$new_response = [];
	foreach (explode('&', $response) as $chunk) {
		$param = explode("=", $chunk);

		if ($param) {
			$new_response[urldecode($param[0])] = isset($param[1]) ? urldecode($param[1]) : null;
		}
	}
	return $new_response;
}

/**
 * Makes a HTTP(S) request using cURL, also processing the response data
 *
 * @param string|null $url
 * @param string $method Uses a HTTPMethod enum value
 * @param array|object $body An array or object that can be converted into a URL-encoded string
 * @param array $headers If provided, the request will send these headers with the request
 * @return array|mixed|string
 * @throws Exception
 */
function ml_http_request ($url, $method = HTTPMethod::GET, $body = null, $headers = []) {
	// Throw errors before making the request if parameters have not been
	// correctly provided.
	if ($url === null || $url === '') throw new Exception('Provide URL');
	if (!HTTPMethod::isValidValue($method)) throw new Exception('Provide correct method');
	if ($method === HTTPMethod::GET && $body !== null) throw new Exception('Cannot send body in GET request');

	$curl = curl_init();

	$settings = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => $url,
		CURLOPT_HTTPHEADER => $headers,
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

	// Execute HTTP request using settings above
	$result = curl_exec($curl);
	$errors = curl_error($curl);

	// Try to decode the response if it's FORM or JSON data
	$response_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

	// Before returning anything, close the curl connection
	curl_close($curl);

	if ($result === false || $errors !== '') {
		return $errors;
	}

	if ($response_type === 'application/json') {
		return json_decode($result);
	} elseif ($response_type === 'application/x-www-form-urlencoded') {
		return ml_formdata_decode($result);
	}

	return $result;
}

function ml_http_bearer () {
	$headers = apache_request_headers();
	if (array_key_exists('Authorization', $headers)) {
		$bearer = $headers['Authorization'];
		if (strpos($bearer, 'Bearer') === 0) {
			return explode(' ', $bearer)[1];
		}
		return $bearer;
	}

	return false;
}
