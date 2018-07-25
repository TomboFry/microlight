<?php

if (!defined('MICROLIGHT_INIT')) die();

class Config {
	/*
	 * Modify these variables before installing on your website:
	 */

	// DB File (string):
	// The location of the SQLite database file, relative to root.
	// default: 'microlight.db'
	const DB_FILE = 'microlight.db';

	// Posts Per Page (integer):
	// How many posts should be shown on the homepage or while searching
	// default: 20
	const POSTS_PER_PAGE = 20;

	// Theme (string):
	// The folder name of the theme you would like to use for this blog
	// default: 'uberlight'
	const THEME = 'uberlight';

	// Title Separator (string):
	// What splits up your name and the post name in the title bar
	// eg. "Your Name | Post Title"
	// default: ' | '
	const TITLE_SEPARATOR = ' | ';

	// Root (string):
	// Must start AND end with a slash.
	// Where your site is hosted relative to the absolute root. For example,
	// if your site's address is 'https://example.com/blog', ROOT would be
	// set to '/blog/'.
	// default: '/'
	const ROOT = '/';

	// Date Pretty (string):
	// A PHP date format string used to format the default ISO8601 published
	// date.
	// default: 'l jS F Y, h:i A'
	const DATE_PRETTY = 'l jS F Y, H:i';
}

/**********************************
 *  DO NOT EDIT BELOW THIS POINT  *
 **********************************/

require_once('lib/enum.php');
require_once('db.include.php');
require_once('functions.include.php');
