<?php

if (!defined('MICROLIGHT')) die();

function get_access_token () {
	// Don't allow user in without a valid bearer token
	$bearer = ml_http_bearer();

	if ($bearer === false) {
		$bearer = post('access_token');
	}

	return $bearer;
}
