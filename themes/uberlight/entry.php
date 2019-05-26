<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

/**
 * Display an entry on the page
 * @param Post $post
 * @param bool $show_permalink
 * @return void
 */
function entry ($post, $show_permalink = true) {
	echo "<article class='h-entry'>";

	entry_title($post, $show_permalink);

	if ($show_permalink === true) {
		echo "<div class='p-summary'>" . $post['summary'] . "</div>";
	} else {
		echo "<div class='e-content'>" . $post['content'] . "</div>";
	}

	entry_footer($post);

	echo "</article>";
}

/**
 * Show the title, if there is one
 * @param Post $post
 * @param bool $show_permalink
 * @return void
 */
function entry_title ($post, $show_permalink = true) {
	if (!ml_post_has_title($post)) return;

	if ($show_permalink == true) {
		echo "<a href='" . ml_post_permalink($post['slug']) . "'>";
	}

	echo "<h2 class='p-name'>";
	echo $post['name'];
	echo "</h2>";

	if ($show_permalink == true) {
		echo "</a>";
	}
}

/**
 * Show the post's footer, including any tags, the date/time, and the type
 * @param Post $post
 * @return void
 */
function entry_footer ($post) {
	$type_link = ml_type_permalink($post['post_type']);
	$post_link = ml_post_permalink($post['slug']);
	$date = ml_date_pretty($post['published']);

	// Open footer
	echo "<footer>";

	// Open tags
	echo "<div class='tags'>";

	// Display a link to the post-type archive
	echo "<a href='" . $type_link . "'>";
	echo $post['post_type'];
	echo "</a>; ";

	// Display links to each of the post's tags
	foreach ($post['tags'] as $key) {
		$link = ml_tag_permalink($key);
		echo "<a class='p-category' href='" . $link . "'>" . $key . "</a>; ";
	}

	// Close tags
	echo "</div>";

	// Display the time the post was created, with a link to the post included
	echo "<a class='u-url u-uid dt-published-link' href='" . $post_link . "'>";
	echo "<time class='dt-published' datetime='" . $post['published'] . "'>";
	echo $date;
	echo "</time>";
	echo "</a>";

	// Close footer
	echo "</footer>";
}
