<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT', 'v0.0.1');

// Load configuration
require_once('includes/config.php');

try {
	// Determine what post to show
	ml_showing();

	ml_database_setup();
	ml_load_posts();
	ml_database_close();
} catch (Exception $e) {
	echo "<h1>Error (likely, for now)</h1>";
	echo "<p><strong>Message:</strong> {$e->getMessage()}</p>";
	echo "<p><strong>Code:</strong> {$e->getCode()}</p>";
	echo "<p>Consider <a href='routes/install.php'>installing</a> if you haven't already.</p>";
	die();
}

// Initialise the theme
require_once("themes/" . Config::THEME . "/index.php");

// Kill the PHP script. Some free web hosts like to inject their tracking
// scripts and this should hopefully prevent that.
die();
