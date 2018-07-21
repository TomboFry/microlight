<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT_INIT', 'true');

// Load configuration
require_once('includes/config.php');

try {
	require_once('includes/db.include.php');

	// Set up a connection to the database
	$db = new DB();

	// Load Identity
	$Me = (new Identity($db))->findOne();

	// For now, simply print the name and description of the user if they
	// are found.
	echo $Me->name . "<br />";
	echo $Me->email . "<br />";
	echo $Me->note . "<br /><br />";

	// Load all classes
	$relme = new RelMe($db);
	$posts = new Post($db);
	$posttags = new PostTag($db);
	$links = $relme->find();

	foreach ($links as $key => $value) {
		echo $value->name . "<br />";
		echo $value->url . "<br /><br/>";
	}

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
