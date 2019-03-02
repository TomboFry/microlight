<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

// Contains various elements for this page
require_once('elements.php');

// Contains everything needed to display a single post
require_once('entry.php');

?>
<!DOCTYPE html>
<html lang="en">
<?php html_head(); ?>
<body>
	<?php html_author(); ?>
	<div class="<?php echo strtolower($showing); ?>">
		<?php
		switch ($showing) {
			case Show::ERROR404:
				echo 'That post could not be found.';
				break;
			case Show::ARCHIVE:
				foreach ($posts as $Post) {
					entry($Post);
				}
				break;
			case Show::POST:
			case Show::PAGE:
				entry($posts, false);
				break;
		}
		?>
	</div>
	<?php html_pagination(); ?>
</body>
</html>
