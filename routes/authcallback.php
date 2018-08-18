<?php

define('MICROLIGHT', 'v0.0.1');

session_start();

chdir('..');
require_once('includes/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') error('Use `GET` method');

list($success, $error) = ml_validate_token();

if ($success === false) {
	die('<pre>Error: ' . $error . '</pre>');
} else {
	header('Location: ' . ml_base_url() . 'routes/admin');
}
