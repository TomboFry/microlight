<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

function post ($post, $show_permalink = true) {
	echo "<article class='h-entry'>";
	if (ml_post_has_name($post)) {
		if ($show_permalink) echo "<a href='" . ml_post_permalink($post->slug) . "'>";
		echo "<h2 class='p-name'>";
		echo $post->name;
		echo "</h2>";
		if ($show_permalink) echo "</a>";
	}

	// Show different content for different post types
	switch ($post->type) {
	case 'audio':
		?>
			<audio class='u-audio' controls>
				<source src='<?php echo $post->url; ?>' />
				<p>Your browser does not support this audio format</p>
			</audio>
		<?php
		break;
	case 'photo':
		?>
			<img
				class='u-photo'
				alt='<?php echo $post->name; ?>'
				src='<?php echo $post->url; ?>'
			/>
		<?php
		break;

	default:
		// Do nothing
		break;
	}

	if ($show_permalink) {
		echo "<p class='p-summary'>" . $post->summary . "</p>";
	} else {
		echo "<div class='e-content'>" . $post->content . "</div>";
	}

	// Everything below this point is for metadata
	?>
	<footer>
		<a class='u-url u-uid' href='<?php echo ml_post_permalink($post->slug); ?>'>
			<time class='dt-published' datetime='<?php echo $post->published; ?>'>
				<?php echo ml_date_pretty($post->published); ?>
			</time>
		</a>
		<div class='tags'>
			<?php
			foreach ($post->tags as $key) {
				echo "<a class='p-category' href='" . ml_tag_permalink($key) . "'>" . $key . "</a>; ";
			}
			?>
		</div>
	<?php

	// Print Location
	if (!$show_permalink):
		$geo = ml_location_geo($post->location);
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
	?>
	</footer>
	</article>
	<?php
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
