<?php

if (!defined('MICROLIGHT_INIT')) die();

class Config {
	/*
	 * Modify these variables before installing on your website:
	 */

	// DB File (string):
	// The location of the SQLite database file, relative to root.
	const DB_FILE = 'microlight.db';

	// Posts Per Page (integer):
	// How many posts should be shown on the homepage or while searching
	const POSTS_PER_PAGE = 20;
}

/**********************************
 *  DO NOT EDIT BELOW THIS POINT  *
 **********************************/

require_once('lib/enum.php');
require_once('db.include.php');
require_once('functions.include.php');

abstract class Show extends BasicEnum {
	const ARCHIVE = 'ARCHIVE';
	const POST = 'POST';
	const PAGE = 'PAGE';
	const ERROR404 = 'ERROR404';
}

$post_slug = '';
$post_tag = '';
$post_type = '';
$search_query = '';
$pagination = null;
$showing = Show::ARCHIVE;
$db = null;
$Me = null;
$Posts = null;
