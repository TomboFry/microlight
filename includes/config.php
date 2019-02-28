<?php

if (!defined('MICROLIGHT')) die();

class Config {
	/*
	 * **Modify these variables before installing on your website:**
	 * If there are any settings you don't understand or know what to set
	 * to, leave them as the default.
	 */

	// DB File (string):
	// The location of the SQLite file or MySQL database, relative to root.
	// default: 'microlight'
	const DB_NAME = 'microlight';

	// Use MySQL (boolean):
	// If false, microlight will use an SQLite database saved to file.
	// If true, microlight will use a MySQL database with the username and
	// password provided below
	// default: false
	const USE_MYSQL = false;

	// MySQL Login Details (string):
	// This only need to be set if you choose to use MySQL (above)
	const MYSQL_HOSTNAME = 'localhost';
	const MYSQL_USERNAME = '';
	const MYSQL_PASSWORD = '';

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

	// Open Graph (boolean):
	// Whether you would like to automatically add Open Graph tags (eg. Link
	// previews for Facebook & Twitter) to your page.
	// default: true
	const OPEN_GRAPH = true;

	// IndieAuth Provider (string):
	// The URL pointing towards your preferred IndieAuth provider. This is
	// important and used for creating posts on your website.
	// default: 'https://indieauth.com/auth'
	const INDIEAUTH_PROVIDER = 'https://indieauth.com/auth';

	// IndieAuth Token Endpoint (string):
	// Similarly to the IndieAuth provider, this URL (which may or may not
	// be identical to the provider URL) is used to validate the token
	// received from the IndieAuth provider you specified above.
	// default: 'https://tokens.indieauth.com/token'
	const INDIEAUTH_TOKEN_ENDPOINT = 'https://tokens.indieauth.com/token';

	// Homepage Feed Post Types (string array):
	// Restricts the homepage to displaying only the following post types
	// default: [ 'note', 'article', 'photo', 'like', 'repost', 'reply', 'bookmark' ]
	const HOMEPAGE_POST_TYPES = [
		'note', 'article', 'photo', 'like', 'repost', 'reply', 'bookmark'
	];
}

/**********************************
 *  DO NOT EDIT BELOW THIS POINT  *
 **********************************/

require_once('lib/enum.php');
require_once('lib/network.php');
require_once('lib/slug.php');
require_once('db.include.php');
require_once('functions.include.php');
