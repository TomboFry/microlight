<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

function html_author_links () {
	echo "<ul class='me-links'>";

	// Display email first
	echo "<li>";
	// Using the `rel=me` attribute and `u-email` class for `h-entry` sites
	echo "<a rel='me' class='u-email' href='mailto:" . User::EMAIL . "'>";
	echo User::EMAIL;
	echo "</a>";
	echo "</li>";

	// Display all links underneath
	foreach (User::IDENTITIES as $identity) {
		echo "<li>";
		// Be sure to include `rel=me` for all user links
		echo "<a rel='me' target='_blank' href='" . $identity['url'] . "'>";
		echo $identity['name'];
		echo "</a>";
		echo "</li>";
	}

	echo "</ul>";
}

function html_author () {
	// These classes indicate the author
	echo "<header class='p-author vcard hcard h-card'>";
	echo "<h1>";
	echo "<a href='";
	echo ml_base_url();
	// These classes represent the author's full name
	echo "' class='p-name u-url uid fn' rel='me'>";
	echo User::NAME;
	echo "</a>";
	echo "</h1>";

	if (!empty(User::NOTE)) {
		echo "<p class='p-note'>" . User::NOTE . "</p>";
	}

	echo html_author_links();
	echo "</header>";
}

function html_pagination () {
	// Only display pagination if we need to
	if (!ml_pagination_enabled()) return;

	// Open pagination
	echo "<div class='pagination'>";

	// Display left pagination
	if (ml_pagination_left_enabled()) {
		echo "<a class='pagination-left' href='";
		echo ml_pagination_left_link();
		echo "'>&lt;&lt; Newer</a>";
	}

	// Display right pagination
	if (ml_pagination_right_enabled()) {
		echo "<a class='pagination-right' href='";
		echo ml_pagination_right_link();
		echo "'>Older &gt;&gt;</a>";
	}

	// Close pagination
	echo '</div>';
}
