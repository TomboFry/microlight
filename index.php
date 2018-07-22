<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT_INIT', 'true');

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
	die();
}

// Show Debug Information (for now)
echo "<strong>Debug:</strong><br />";
echo "Slug: '$post_slug'<br />";
echo "Tag: '$post_tag'<br />";
echo "Type: '$post_type'<br />";
echo "Page: '$pagination'<br />";
echo "Search Query: '$search_query'<br />";
echo "Showing: '$showing'<br />";

echo '<pre>' . var_export($Me, true) . '</pre>';
echo '<pre>' . var_export($Posts, true) . '</pre>';

// Kill the PHP script. Some free webhosts like to inject their tracking scripts
// and this should hopefully prevent that.
die();
