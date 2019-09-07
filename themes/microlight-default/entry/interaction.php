<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

/**
 * Add interactions to a single post
 * @param Post $post
 * @return void
 */
function entry_interactions ($post) {
	if (isset($post['interactions']) === false) return;

	// Open interactions section container
	echo "<div id='entry-interactions'>";

	foreach ($post['interactions'] as $index => $interaction) {
		switch ($interaction['type']) {
		case 'like':
		case 'favourite':
			entry_like($interaction);
			break;

		case 'repost':
			entry_repost($interaction);
			break;

		case 'bookmark':
			entry_bookmark($interaction);
			break;

		case 'reply':
		case 'comment':
		default:
			entry_reply($interaction);
			break;
		}
	}

	// Close interactions section container
	echo "</div>";
}

function entry_interaction_metadata ($interaction) {
	// Metadata (date, link to post, etc).
	echo "<a class='u-url' href='" . $interaction['url'] . "'>";
	echo "<time class='dt-published'>" . ml_date_pretty($interaction['datetime']) . "</time>";
	echo "</a>";
}

/**
 * Markup for 'reply' interactions
 * @param Interaction $interaction
 * @return void
 */
function entry_reply ($interaction) {
	// Open container
	echo "<div class='p-comment u-comment h-cite'>";

	echo "<div class='p-author u-author h-card'>";
	echo "<img src='" . $interaction['person']['photo_url'] . "' class='u-photo'>";
	echo "<a class='u-url p-name' href='" . $interaction['person']['url'] . "'>";
	echo $interaction['person']['name'];
	echo "</a>";
	echo "</div>";

	// Content
	echo "<p class='p-content'>";
	echo $interaction['contents'];
	echo "</p>";

	entry_interaction_metadata($interaction);

	// Close container
	echo "</div>";
}

/**
 * Markup for 'like' or 'favourite' interactions
 * @param Interaction $interaction
 * @return void
 */
function entry_like ($interaction) {
	// Open container
	echo "<div class='p-like u-like h-cite'>";

	echo "<span class='entry-interaction-emoji'>&#10084;&#65039;</span>";

	// Author
	echo "<div class='p-author u-author h-card'>";
	echo "<img src='" . $interaction['person']['photo_url'] . "' class='u-photo'>";
	echo "<a class='u-url p-name' href='" . $interaction['person']['url'] . "'>";
	echo $interaction['person']['name'];
	echo "</a> liked this post.";
	echo "</div>";

	entry_interaction_metadata($interaction);

	// Close container
	echo "</div>";
}

/**
 * Markup for 'repost' interactions
 * @param Interaction $interaction
 * @return void
 */
function entry_repost ($interaction) {
	// Open container
	echo "<div class='p-repost u-repost h-cite'>";

	// Repost emoji
	echo "<span class='entry-interaction-emoji'>&#128260;</span>";

	// Author
	echo "<div class='p-author u-author h-card'>";
	echo "<img src='" . $interaction['person']['photo_url'] . "' class='u-photo'>";
	echo "<a class='u-url p-name' href='" . $interaction['person']['url'] . "'>";
	echo $interaction['person']['name'];
	echo "</a> reposted this post.";
	echo "</div>";

	entry_interaction_metadata($interaction);

	// Close container
	echo "</div>";
}

/**
 * Markup for 'bookmark' interactions
 * @param Interaction $interaction
 * @return void
 */
function entry_bookmark ($interaction) {
	// Open container
	echo "<div class='p-bookmark u-bookmark h-cite'>";

	echo "<span class='entry-interaction-emoji'>&#128278;</span>";

	// Author
	echo "<div class='p-author u-author h-card'>";
	echo "<img src='" . $interaction['person']['photo_url'] . "' class='u-photo'>";
	echo "<a class='u-url p-name' href='" . $interaction['person']['url'] . "'>";
	echo $interaction['person']['name'];
	echo "</a> bookmarked this post.";
	echo "</div>";

	entry_interaction_metadata($interaction);

	// Close container
	echo "</div>";
}
