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
	$Me->links = (new RelMe($db))->find([
		[
			'column' => 'identity_id',
			'operator' => SQLOP::EQUAL,
			'value' => $Me->id
		]
	]);

	$Posts = (new Post($db))->find([
		[
			'column' => 'identity_id',
			'operator' => SQLOP::EQUAL,
			'value' => $Me->id
		]
	]);

	foreach ($Posts as $key => $value) {
		echo "$value->content<br/>";
		echo "$value->published<br/>";
		echo implode(' - ', $value->tags) . "<br/>";
		echo "<br/>";
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
