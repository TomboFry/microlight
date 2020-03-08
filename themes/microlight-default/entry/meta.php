<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

/**
 * Displays the title of the post, if there is one, with an optional permalink
 *
 * @param Post $post
 * @param boolean $is_archive
 * @return void
 */
function entry_title ($post, $is_archive) {
	if (ml_post_has_title($post) !== true) return;

	if ($is_archive === true) {
		echo "<a href='" . ml_post_permalink($post['slug']) . "'>";
	}

	echo "<h2 class='p-name'>";
	echo $post['name'];
	echo "</h2>";

	if ($is_archive === true) {
		echo "</a>";
	}
}

/**
 * Displays the post's actual content, although may show the summary if viewing
 * the archive/index page
 *
 * @param Post $post
 * @param boolean $is_archive
 * @return void
 */
function entry_content ($post, $is_archive) {
	if ($is_archive === true) {
		echo "<p class='p-summary'>" . $post['summary'] . "</p>";
	} else {
		echo "<div class='e-content'>" . $post['content'] . "</div>";
	}
}

/**
 * Displays the metadata at the bottom of a post
 *
 * @param Post $post
 * @param boolean $is_archive
 * @return void
 */
function entry_footer ($post, $is_archive) {
	?>
	<footer>
	<div class='tags'>
		<a href='<?php echo ml_type_permalink($post['post_type']); ?>'><?php
			echo $post['post_type'];
		?></a>
		<?php
		foreach ($post['tags'] as $key) {
			echo "<a class='p-category' href='" . ml_tag_permalink($key) . "'>" . $key . "</a> ";
		}
		?>
	</div>
	<a class='u-url u-uid dt-published-link' href='<?php echo ml_post_permalink($post['slug']); ?>'>
		<time class='dt-published' datetime='<?php echo $post['published']; ?>'>
			<?php echo ml_date_pretty($post['published']); ?>
		</time>
	</a>
	<?php if ($post['updated'] !== null && $is_archive === false): ?>
	<time class='dt-updated' datetime='<?php echo $post['updated']; ?>'>
		Updated on <?php echo ml_date_pretty($post['updated']); ?>
	</time>
	<?php endif; ?>
	<?php

	// Print Location
	if (!$is_archive && SHOW_MAP_ON_POSTS === true):
		$geo = ml_location_geo($post['location']);
		if (is_array($geo)):
			$link = 'https://www.openstreetmap.org/?mlat=' . $geo['lat'] . '&mlon=' . $geo['long'];
			?>
			<div class='h-geo'>
				&#x1F4CD;
				<a
					target='_blank'
					href='<?php echo $link; ?>'
				>
					<span class='p-latitude'><?php echo $geo['lat']; ?></span>,
					<span class='p-longitude'><?php echo $geo['long']; ?></span>
				</a>
			</div>
		<?php
		elseif (is_string($geo)):
			?>
			<div class='h-adr'>
				&#x1F4CD;
				<a
					target='_blank'
					class='p-street-address'
					href='https://www.openstreetmap.org/search?query=<?php echo $geo; ?>'
				>
					<?php echo $geo; ?>
				</a>
			</div>
		<?php
		endif;
	endif;

	echo '</footer>';
}

function entry_hcard () {
	echo "<div class='p-author vcard hcard h-card hidden' rel='author'>";
		if (ml_user_has_icon()) {
			echo "<img class='u-photo' alt='" . User::NAME . "' src='" . ml_icon_url() . "' />";
		}
		echo "<a href='";
		echo ml_base_url();
		echo "' class='p-name u-url uid fn' rel='me'>";
			echo User::NAME;
		echo "</a>";
	echo "</div>";
}
