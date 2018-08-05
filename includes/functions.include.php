<?php

if (!defined('MICROLIGHT')) die();

abstract class Show extends BasicEnum {
	const ARCHIVE = 'ARCHIVE';
	const POST = 'POST';
	const PAGE = 'PAGE';
	const ERROR404 = 'ERROR404';
}

// Define all globally available variables
$post_slug = '';
$post_tag = '';
$post_type = '';
$search_query = '';
$post_total_count = 0;
$pagination = null;
$showing = Show::ARCHIVE;
$db = null;
$me = null;
$posts = null;

function ml_get_not_blank ($var) {
	return (isset($_GET[$var]) && $_GET[$var] !== "");
}

function ml_post_not_blank ($var) {
	return (isset($_POST[$var]) && $_POST[$var] !== "");
}

function ml_showing () {
	global $post_slug;
	global $post_tag;
	global $post_type;
	global $pagination;
	global $search_query;
	global $showing;

	if (ml_get_not_blank('post_slug')) {
		$post_slug = $_GET['post_slug'];
		$showing = Show::PAGE;
	} else {
		$showing = Show::ARCHIVE;
		$pagination = 0;

		if (ml_get_not_blank('post_tag')) {
			$post_tag = $_GET['post_tag'];
		}

		if (ml_get_not_blank('post_type')) {
			$post_type = $_GET['post_type'];
		}

		if (ml_get_not_blank('search_query')) {
			// Override other two fields if Search Query is present
			$post_tag = '';
			$post_type = '';

			$search_query = $_GET['search_query'];
		}

		if (ml_get_not_blank('page')) {
			$pagination = $_GET['page'] - 1;
			if ($pagination < 0) {
				throw new Exception("Page $pagination cannot be less than 0");
			}
		}
	}
}

function ml_database_setup () {
	global $db;
	global $me;

	// Set up a connection to the database
	$db = new DB();

	// Load Identity
	$me = (new Identity($db))->find_one();
	if ($me !== null) {
		$me->links = (new RelMe($db))->find();
		$me->home = ml_base_url();
	}
}

function ml_load_posts () {
	global $db;
	global $post_slug;
	global $post_tag;
	global $post_type;
	global $search_query;
	global $post_total_count;
	global $pagination;
	global $showing;
	global $posts;

	$where = [];
	$limit = Config::POSTS_PER_PAGE;
	$offset = Config::POSTS_PER_PAGE * $pagination;

	if ($post_slug !== '') {
		$limit = 1;
		$offset = 0;
		$where = [
			[
				'column' => 'slug',
				'operator' => SQLOP::EQUAL,
				'value' => $post_slug,
				'escape' => SQLEscape::SLUG,
			],
		];
	} elseif ($post_tag !== '' || $post_type !== '') {
		if ($post_tag !== '') {
			array_push($where, [
				'column' => 'tags',
				'operator' => SQLOP::LIKE,
				'value' => "%$post_tag,%",
				'escape' => SQLEscape::TAG,
			]);
		}
		if ($post_type !== '') {
			array_push($where, [
				'column' => 'type',
				'operator' => SQLOP::EQUAL,
				'value' => $post_type,
				'escape' => SQLEscape::TYPE,
			]);
		}
	} elseif ($search_query !== '') {
		$where = [
			[
				'column' => 'name',
				'operator' => SQLOP::LIKE,
				'value' => "%$search_query%",
				'escape' => SQLEscape::NONE,
			],
		];
	}

	// Run the SQL query
	$post_class = new Post($db);
	$posts = $post_class->find($where, $limit, $offset);
	$post_total_count = $post_class->count($where);

	// If we're asking for a page or post, there should only ever be one
	// result, so process that here:
	if ($showing === Show::POST || $showing === Show::PAGE) {
		// If there is not 1 post, show a 404 error
		if (count($posts) !== 1) {
			$showing = Show::ERROR404;
			$posts = null;
		} else {
			// Otherwise, take the only post out of the array
			$posts = $posts[0];
		}
	}
}

// Close DB connection
function ml_database_close () {
	global $db;
	$db->close();
}

function ml_get_name () {
	global $me;

	return $me->name;
}

// Returns the title, depending on whether you're on a single post or not.
function ml_get_title () {
	global $showing;
	global $posts;
	global $me;

	$str = "";
	if ($showing === Show::POST || $showing === Show::PAGE) {
		$str .= $posts->name . Config::TITLE_SEPARATOR;
	}
	$str .= $me->name;
	return $str;
}

// Returns the full URL, including "http(s)"
function ml_base_url () {
	return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . Config::ROOT;
}

// Returns an absolute URL to a specific post
function ml_post_permalink ($slug) {
	return ml_base_url() . '?post_slug=' . $slug;
}

// Returns an absolute URL to the archive of a specific tag
function ml_tag_permalink ($tag) {
	return ml_base_url() . '?post_tag=' . $tag;
}

function ml_current_page_permalink () {
	global $post_tag;
	global $post_type;
	global $search_query;
	$str = ml_base_url() . '?';
	if ($search_query !== '') {
		$str .= "search_query=$search_query&";
	} elseif ($post_tag !== '' || $post_type !== '') {
		$str = ml_base_url() . '?';
		if ($post_tag !== '') $str .= "post_tag=$post_tag&";
		if ($post_type !== '') $str .= "post_type=$post_type&";
	}
	return $str;
}

// Returns an absolute URL pointing towards the directory of the currently
// selected theme
function ml_get_theme_dir () {
	return ml_base_url() . 'themes/' . Config::THEME;
}

// Prints an ISO8601 date in the format defined in the configuration
function ml_date_pretty ($date) {
	return date(
		Config::DATE_PRETTY,
		strtotime($date)
	);
}

function ml_pagination_enabled () {
	global $showing;
	global $post_total_count;

	// Only ever show pagination on archive pages
	if ($showing !== Show::ARCHIVE) return false;

	// Don't show pagination if there are less posts than there should be
	// displayed on a page
	if ($post_total_count <= Config::POSTS_PER_PAGE) return false;

	// Otherwise, we have pagination!
	return true;
}

function ml_pagination_left_enabled () {
	global $pagination;
	return $pagination > 0;
}

function ml_pagination_right_enabled () {
	global $pagination;
	global $post_total_count;
	$total = ceil($post_total_count / Config::POSTS_PER_PAGE) - 1;
	return $pagination < $total;
}

function ml_pagination_left_link () {
	global $pagination;
	return ml_current_page_permalink() . "page=$pagination";
}

function ml_pagination_right_link () {
	global $pagination;
	return ml_current_page_permalink() . "page=" . ($pagination + 2);
}

// Determines whether a location from the database are coordinates or an address
function ml_location_geo ($location) {
	// Don't even try parsing if it's empty
	if ($location === '' || $location === null) return $location;

	// Float Regex, taken from: https://stackoverflow.com/a/12643073
	$float = '([+-]?([0-9]*[.])?[0-9]+)';
	$full_regex = '/^' . $float . ',' . $float . '$/';

	// If it matches our regex, then it's a geo-location, not an address
	if (preg_match($full_regex, $location, $matches)) {
		$lat = (float)$matches[1];
		$long = (float)$matches[3];

		// Make sure the latitude and longitude are within
		// sensible boundaries
		if ($lat > 180 || $lat < -180 || $long > 90 || $long < -90) {
			return $location;
		}
		return ['lat' => $lat, 'long' => $long];
	} else {
		return $location;
	}
}

function ml_post_has_name ($post) {
	return $post->name !== null && $post->name !== '';
}
