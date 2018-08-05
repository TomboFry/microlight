<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

require_once('helper.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title><?php echo ml_get_title(); ?></title>
	<link rel='stylesheet' href='<?php echo ml_get_theme_dir(); ?>/css/style.css' />
</head>
<body>
	<header class="p-author vcard hcard h-card">
		<h1>
			<a href="<?php echo $me->home; ?>" class="p-name u-url uid fn" rel="me">
				<?php echo $me->name; ?>
			</a>
		</h1>
		<?php echo links($me); ?>
	</header>
	<div class="<?php echo strtolower($showing); ?>">
		<?php
		if ($showing === Show::ERROR404) {
			echo "That post could not be found.";
		} elseif ($showing === Show::ARCHIVE) {
			foreach ($posts as $Post) {
				echo post($Post);
			}
		} elseif ($showing === Show::POST || $showing === Show::PAGE) {
			echo post($posts, false);
		}
		?>
	</div>
	<?php if (ml_pagination_enabled()) { ?>
	<div class='pagination'>
		<?php if (ml_pagination_left_enabled()) { ?>
		<a href='<?php echo ml_pagination_left_link(); ?>'>&lt;&lt; Newer</a>
		<?php } ?>
		<?php if (ml_pagination_right_enabled()) { ?>
		<a href='<?php echo ml_pagination_right_link(); ?>'>Older &gt;&gt;</a>
		<?php } ?>
	</div>
	<?php } ?>
</body>
</html>
