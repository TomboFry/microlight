<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT_INIT')) die();

function post ($post, $show_permalink = true) {
	echo "<article class='h-entry'>";
	if ($post->name !== '' && $post->name !== NULL) {
		if ($show_permalink) echo "<a href='" . ml_post_permalink($post) . "'>";
		echo "<h2 class='p-name'>";
		echo $post->name;
		echo "</h2>";
		if ($show_permalink) echo "</a>";
	}
	if ($show_permalink) {
		echo "<p class='p-summary'>" . $post->summary . "</p>";
	} else {
		echo "<div class='e-content'>" . $post->content . "</div>";
	}
	echo "<footer>";
		echo "<a class='u-url u-uid' href='" . ml_post_permalink($post) . "'>";
			echo "<time class='dt-published' datetime='$post->published'>";
			echo ml_date_pretty($post->published);
			echo "</time>";
		echo "</a>";
		echo "<div class='tags'>";
			foreach ($post->tags as $key) {
				echo "<a class='p-category' href='" . ml_tag_permalink($key) . "'>" . $key . "</a>; ";
			}
		echo "</div>";
	echo "</footer>";
	echo "</article>";
}

function links ($me) {
	echo "<ul>";

	// Display Email at the top
	echo "<li>";
	echo "<a rel='me' class='u-email' href='mailto:$me->email'>";
	echo $me->email;
	echo "</a>";
	echo "</li>";

	// Display all links underneath
	foreach ($me->links as $value) {
		echo "<li>";
		echo "<a rel='me' target='_blank' href='$value->url'>";
		echo $value->name;
		echo "</a>";
		echo "</li>";
	}
	echo "</ul>";
}
