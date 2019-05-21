<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

function fmt_default ($post, $is_archive) {
	entry_title($post, $is_archive);
	entry_content($post, $is_archive);
}

function fmt_image ($post, $is_archive) {
	entry_title($post, $is_archive);
	echo "<img ";
	echo "class='u-photo' ";
	echo "alt='" . $post->name . "' ";
	echo "src='" . $post->url . "' />";
	entry_content($post, $is_archive);
}

function fmt_audio ($post, $is_archive) {
	entry_title($post, $is_archive);
	echo "<audio class='u-audio' controls>";
		echo "<source src='" . $post->url . "' />";
		echo "<p>Your browser does not support this audio format</p>";
	echo "</audio>";
	entry_content($post, $is_archive);
}

function fmt_bookmark ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "Bookmarked ";
		echo "<a class='u-bookmark-of h-cite' href='" . $post->url . "'>";
			echo $post->name;
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_like ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "Liked ";
		echo "<a class='u-like-of' href='" . $post->url . "'>";
			echo $post->name;
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_reply ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "Replied to ";
		echo "<a class='in-reply-to' href='" . $post->url . "'>";
			echo $post->url;
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}
