<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

require_once('meta.php');
require_once('formats.php');

/**
 * The main logic for displaying entries
 *
 * @param Post $post
 * @param boolean $is_archive
 * @return void
 */
function entry ($post, $is_archive = true) {
	echo "<article class='h-entry'>";

	// Show different content for different post types
	switch ($post->post_type) {
	case 'audio':
		fmt_audio($post, $is_archive);
		break;
	case 'photo':
		fmt_image($post, $is_archive);
		break;
	case 'bookmark':
		fmt_bookmark($post, $is_archive);
		break;
	case 'like':
		fmt_like($post, $is_archive);
		break;
	default:
		fmt_default($post, $is_archive);
		break;
	}

	entry_footer($post, $is_archive);

	// Everything below this point is for metadata
	echo '</article>';
}
