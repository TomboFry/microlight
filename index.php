<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT_INIT', 'true');

// Load configuration
require('includes/config.php');

// Set up a connection to the database
try {
	require('includes/db.include.php');
	$db = new DB();

	// Load Identity
	$identity = new Identity($db);

	// For now, simply print the name and description of the user if they
	// are found.
	$me = $identity->findOne();
	echo $me['name'] . "<br />";
	echo $me['email'] . "<br />";
	echo $me['note'] . "<br />";

	// Load all classes
	$relme = new RelMe($db);
	$posts = new Post($db);
	$posttags = new PostTag($db);

	// Close DB connection
	$db->close();
} catch (Exception $e) {
	echo "<h1>Error (likely, for now)</h1>";
	echo "<p><strong>Message:</strong> {$e->getMessage()}</p>";
	echo "<p><strong>Code:</strong> {$e->getCode()}</p>";
}

// Kill the PHP script. Some free webhosts like to inject their tracking scripts
// and this should hopefully prevent that.
die();
