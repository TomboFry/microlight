<?php

if (!defined('MICROLIGHT')) die();

// https://stackoverflow.com/a/2955878
function slugify ($text) {
	// replace non letter or digits by -
	$text = preg_replace('~[^\pL\d]+~u', '-', $text);

	// transliterate
	if (function_exists('iconv')) {
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	}

	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	// trim
	$text = trim($text, '-');

	// lowercase
	$text = strtolower($text);

	// Add the date to the start, and limiting it to 64 characters.
	$text = date('omd') . '-' . substr($text, 0, 64);

	// remove duplicate -
	return preg_replace('~-+~', '-', $text);
}
