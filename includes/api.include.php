<?php

if (!defined('MICROLIGHT')) die();

require_once('lib/enum.php');

abstract class TokenScope extends BasicEnum {
	const CREATE = 'create';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const MEDIA = 'media';
}

$post = null;
$auth = null;

/**
 * Retrieve the HTTP method used, usually `GET` or `POST`
 *
 * @return void
 */
function ml_api_method () {
	return $_SERVER['REQUEST_METHOD'];
}

/**
 * Retrieve the Content-Type header provided in the request
 *
 * @return string
 */
function ml_api_content_type () {
	$content_type = '';

	if (isset($_SERVER['CONTENT_TYPE'])) {
		$content_type = $_SERVER['CONTENT_TYPE'];
	}

	if (empty($content_type) && isset($_SERVER['HTTP_CONTENT_TYPE'])) {
		$content_type = $_SERVER['HTTP_CONTENT_TYPE'];
	}

	// Default to form data if no value was provided.
	if (empty($content_type)) $content_type = HTTPContentType::FORM_DATA;

	return $content_type;
}

/**
 * Decode POST body values, depending on whether JSON was provided.
 *
 * @return bool
 */
function ml_api_post_decode () {
	global $post;

	if (ml_api_content_type() === HTTPContentType::JSON) {
		$post = json_decode(file_get_contents('php://input'), true);
	} else {
		$post = $_POST;
	}

	return true;
}

/**
 * Retrieve a POST value, if provided, otherwise `null`
 *
 * @param string $key
 * @return mixed|null
 */
function ml_api_post ($key) {
	global $post;

	if ($post === null) {
		throw new Exception('Post data has not be initialised. Make sure ml_api_post_decode has been called.');
	}

	if (isset($post[$key]) && !empty($post[$key])) return $post[$key];
	return null;
}

/**
 * Retrieve a POST value in microformats2 syntax, if provided, otherwise null
 *
 * @param array $post
 * @param string $key
 * @param bool $is_single
 * @return mixed|null
 */
function ml_api_post_json ($post, $key, $is_single = false) {
	if (!is_array($post)) return null;
	if (!isset($post[$key])) return null;
	if (empty($post[$key])) return null;
	if (!is_array($post[$key])) return $post[$key];

	// A lot of microformats2 items are intentionally single element arrays.
	// I'm not sure why, but if we know it's supposed to, parse it here.
	if ($is_single === true) {
		if (count($post[$key]) !== 1) return null;
		return $post[$key][0];
	}

	return $post[$key];
}

/**
 * Retrieve a GET value, if provided, otherwise `null`
 *
 * @param string $key
 * @return mixed|null
 */
function ml_api_get ($key) {
	if (isset($_GET[$key]) && !empty($_GET[$key])) return $_GET[$key];
	return null;
}

/**
 * Returns the access token provided by the user, either through the
 * `Authorization` header, or as an `access_token` key in the POST body.
 *
 * @return null|string
 */
function ml_api_access_token () {
	global $post;

	// Don't allow user in without a valid bearer token
	$bearer = ml_http_bearer();

	if ($bearer === false && $post !== null) {
		$bearer = ml_api_post('access_token');
	}

	return $bearer;
}

/**
 * Validates the token with the provided token endpoint. Should be used with
 * all micropub requests.
 *
 * @param string $token The bearer access token
 * @return boolean `true`, if the access token is valid, otherwise `false`
 */
function ml_api_validate_token ($token) {
	global $auth;

	$headers = [
		'Authorization: Bearer ' . $token,
		'Content-Type: application/json',
		'Accept: application/json'
	];

	$response = ml_http_request(
		Config::INDIEAUTH_TOKEN_ENDPOINT,
		HTTPMethod::GET,
		null,
		$headers
	);

	if (empty($response['body']['me'])) return false;
	if ($response['body']['me'] !== ml_base_url()) return false;

	$auth = $response['body'];
	$auth['scope'] = explode(' ', $auth['scope']);

	return true;
}
