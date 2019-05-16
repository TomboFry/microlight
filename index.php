<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT', 'v0.0.1');

try {
	// Load user details
	if (!file_exists('includes/user.config.php')) {
		throw new Exception(
			'User config file not found. Please run /install.php'
		);
	}
	require_once('includes/user.config.php');

	// Load configuration
	require_once('includes/config.php');

	// Determine what post to show
	ml_showing();

	ml_database_setup();
	ml_load_posts();
	ml_database_close();
} catch (\Throwable $e) {
	// TODO: Display a nice error message here instead. Reset global variables
	// and set `$showing` to an error code which can be displayed by the theme.
	echo "<h1>Error</h1>";
	echo "<p><strong>Message:</strong> {$e->getMessage()}</p>";
	echo "<p><strong>Code:</strong> {$e->getCode()}</p>";
	echo "<p>Consider <a href='install.php'>installing</a> if you haven't already.</p>";
	die();
}

// Initialise the theme
require_once("themes/" . Config::THEME . "/index.php");

// Kill the PHP script. Some free web hosts like to inject their tracking
// scripts and this should hopefully prevent that.
die();
