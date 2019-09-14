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
	entry_interactions($post);

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

function entry_interactions ($post) {
	if (isset($post['interactions']) === false) return;

	// Open interactions container
	echo "<div class='entry-interactions'>";

	foreach ($post['interactions'] as $interaction) {
		entry_interaction_single($interaction);
	}

	// Close interactions container
	echo "</div>";
}

function entry_interaction_single ($interaction) {
	// Open container
	echo "<div class='p-comment u-comment h-cite'>";

	// Open author container
	echo "<div class='p-author u-author h-card'>";

	// Display the image, only if the person has one.
	if ($interaction['person']['photo_url'] !== null) {
		echo "<img src='" . $interaction['person']['photo_url'] . "' class='u-photo'>";
	}

	// Display the person's name and URL to their homepage
	echo "<a class='u-url p-name' href='" . $interaction['person']['url'] . "'>";
	echo $interaction['person']['name'];
	echo "</a>";
	echo "</div>";

	// Open content container
	echo "<p class='p-content'>";

	if ($interaction['type'] === 'reply') {
		// Display webmention contents
		echo $interaction['contents'];
	} else {
		// Convert the interaction type into a verb and display it.
		$type = $interaction['type'];
		// Add an 'e' to the end if there isn't one already
		if ($type[-1] !== 'e') $type .= 'e';
		// Always add the D ;)
		$type .= 'd';
		// Print it out.
		echo $type . ' this post.';
	}

	// Close content container
	echo "</p>";

	// Display metadata (date, link to post, etc).
	echo "<a class='u-url' href='" . $interaction['url'] . "'>";
	echo "<time class='dt-published'>" . ml_date_pretty($interaction['datetime']) . "</time>";
	echo "</a>";

	// Close container
	echo "</div>";
}
