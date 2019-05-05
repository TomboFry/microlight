<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

require_once('meta.php');
require_once('entry.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php
	ml_page_headers(); // Add pre-generated headers
	?>
</head>
<body>
	<?php html_author(); ?>
	<main>

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
	</main>
</body>
</html>
