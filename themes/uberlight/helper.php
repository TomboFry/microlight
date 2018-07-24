<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT_INIT')) die();

function post ($Post, $showPermalink = true) {
	echo "<article class='h-entry'>";
	if ($Post->name !== '' && $Post->name !== NULL) {
		if ($showPermalink) echo "<a href='" . ml_post_permalink($Post) . "'>";
		echo "<h2 class='p-name'>";
		echo $Post->name;
		echo "</h2>";
		if ($showPermalink) echo "</a>";
	}
	if ($showPermalink) {
		echo "<p class='p-summary'>" . $Post->summary . "</p>";
	} else {
		echo "<div class='e-content'>" . $Post->content . "</div>";
	}
	echo "<footer>";
		echo "<a class='u-url u-uid' href='" . ml_post_permalink($Post) . "'>";
			echo "<time class='dt-published' datetime='$Post->published'>";
			echo ml_date_pretty($Post->published);
			echo "</time>";
		echo "</a>";
		echo "<div class='tags'>";
			foreach ($Post->tags as $key) {
				echo "<a class='p-category' href='" . ml_tag_permalink($key) . "'>" . $key . "</a>; ";
			}
		echo "</div>";
	echo "</footer>";
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
	echo "</ul>";
}
