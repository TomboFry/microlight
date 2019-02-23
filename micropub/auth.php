<?php

if (!defined('MICROLIGHT')) die();

/**
 * Returns the access token provided by the user, either through the
 * `Authorization` header, or as an `access_token` key in the POST body.
 *
 * @return null|string
 */
function get_access_token () {
	// Don't allow user in without a valid bearer token
	$bearer = ml_http_bearer();

	if ($bearer === false) {
		$bearer = post('access_token');
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
function validate_token ($token) {
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

	if (empty($response->me)) return false;
	if ($response->me === ml_base_url()) return true;
}
