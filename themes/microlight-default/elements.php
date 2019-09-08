<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

function html_head () {
	echo "<head>";
		echo "<meta charset='UTF-8' />";
		echo "<meta name='viewport' content='width=device-width, initial-scale=1.0' />";
		echo "<meta http-equiv='X-UA-Compatible' content='ie=edge' />";

		// Add pre-generated headers
		ml_page_headers();

		// Add this theme's stylesheet
		echo "<link rel='stylesheet' href='";
		echo ml_get_theme_dir();
		echo "/css/style.css' />";
	echo "</head>";
}

function html_author_links () {
	echo "<ul class='me-links'>";

	// Display email first
	echo "<li>";
		echo "<a rel='me' class='u-email' href='mailto:" . User::EMAIL . "'>";
			echo User::EMAIL;
		echo "</a>";
	echo "</li>";

	// Display all links underneath
	foreach (User::IDENTITIES as $value) {
		echo "<li>";
			echo "<a rel='me' target='_blank' href='" . $value['url'] . "'>";
				echo $value['name'];
			echo "</a>";
		echo "</li>";
	}

	echo "</ul>";
}

function html_author () {
	echo "<header class='p-author vcard hcard h-card'>";
		if (ml_user_has_icon()) {
			echo "<img class='u-photo' alt='" . User::NAME . "' src='" . ml_icon_url() . "' />";
		}
		echo "<h1>";
			echo "<a href='";
			echo ml_base_url();
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
	if (ml_pagination_enabled()) {
		echo "<div class='pagination'>";

		if (ml_pagination_left_enabled()) {
			echo "<a class='pagination-left' href='";
			echo ml_pagination_left_link();
			echo "'>&lt;&lt; Newer</a>";
		}
		if (ml_pagination_right_enabled()) {
			echo "<a class='pagination-right' href='";
			echo ml_pagination_right_link();
			echo "'>Older &gt;&gt;</a>";
		}

		echo '</div>';
	}
}
