<?php

define('MICROLIGHT', 'v0.0.1');

session_start();
require_once('../../includes/config.php');

if (!isset($_SESSION['access_token'])) {
	$_SESSION['state'] = ml_generate_token();

	$location = 'https://indieauth.com/auth?' .
		'me=' . ml_base_url() . '&' .
		'client_id=' . ml_base_url() . '&' .
		'redirect_uri=' . ml_base_url() . 'routes/authcallback.php&' .
		'state=' . $_SESSION['state'];

	header('Location: ' . $location);
	return;
}
