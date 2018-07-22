<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT_INIT')) die();

function post ($Post, $showPermalink = true) {
	echo "<article class='h-entry'>";
	if ($Post->name !== '' && $Post->name !== NULL) {
		echo "<h2>";
		if ($showPermalink) echo "<a href='" . ml_get_permalink($Post) . "'>";
		echo $Post->name;
		if ($showPermalink) echo "</a>";
		echo "</h2>";
	}
	echo "<p>" . $Post->summary . "</p>";
	echo "<a href='" . ml_get_permalink($Post) . "'>" . $Post->published . "</a>";
	echo "</article>";
}

function links ($Me) {
	echo "<ul>";

	// Display Email at the top
	echo "<li>";
	echo "<a rel='me' class='u-email' href='mailto:$Me->email'>";
	echo $Me->email;
	echo "</a>";
	echo "</li>";

	// Display all links underneath
	foreach ($Me->links as $value) {
		echo "<li>";
		echo "<a rel='me' target='_blank' href='$value->url'>";
		echo $value->name;
		echo "</a>";
		echo "</li>";
	}
}
