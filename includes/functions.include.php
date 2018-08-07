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
	return (isset($_GET[$var]) && $_GET[$var] !== '');
}

function ml_post_not_blank ($var) {
	return (isset($_POST[$var]) && $_POST[$var] !== '');
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
				throw new Exception('Page ' . $pagination . ' cannot be less than 0');
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

	$str = '';
	if ($showing === Show::POST || $showing === Show::PAGE) {
		$str .= $posts->name !== ''
			? $posts->name
			: $posts->summary;
		$str .= Config::TITLE_SEPARATOR;
	}
	$str .= $me->name;
	return $str;
}

// Returns the full URL, including 'http(s)'
function ml_base_url () {
	return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . Config::ROOT;
}

function ml_icon_url () {
	return ml_base_url() . 'images/me.jpg';
}

// Returns an absolute URL to a specific post
function ml_post_permalink ($slug) {
	return ml_base_url() . '?post_slug=' . $slug;
}

// Returns an absolute URL to the archive of a specific tag
function ml_tag_permalink ($tag) {
	return ml_base_url() . '?post_tag=' . $tag;
}

// Return an absolute, consistent URL that will point to the "official" URL for
// the page currently being viewed
// (see: https://en.wikipedia.org/wiki/Canonical_link_element)
function ml_canonical_permalink () {
	global $post_tag;
	global $post_type;
	global $search_query;
	global $post_slug;

	if ($search_query !== '') {
		$str = ml_base_url() . '?search_query=' . $search_query;
	} elseif ($post_tag !== '' || $post_type !== '') {
		$str = ml_base_url() . '?';
		$acc = [];
		if ($post_tag !== '') array_push($acc, 'post_tag=' . $post_tag);
		if ($post_type !== '') array_push($acc, 'post_type=' . $post_type);
		$str .= implode('&', $acc);
	} elseif ($post_slug !== '') {
		$str = ml_base_url() . '?post_slug=' . $post_slug;
	} else {
		$str = ml_base_url();
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

// Add headers to <head /> tag in theme (highly recommended to use)
function ml_page_headers () {
	global $me;
	global $showing;
	global $posts;

	if ($showing === Show::PAGE || $showing === Show::POST) {
		$description = $posts->summary;
		if ($posts->type === 'photo') {
			$image = $posts->url;
		} else {
			$image = ml_icon_url();
		}
	} else {
		$description = $me->note;
		$image = ml_icon_url();
	}
	?>
	<meta name='description' content='<?php echo $description; ?>' />
	<meta name='generator' content='Microlight <?php echo MICROLIGHT; ?>' />
	<meta name='author' content='<?php echo $me->name; ?>' />
	<meta name='referrer' content='origin'>
	<?php if (Config::OPEN_GRAPH === true): ?>
		<meta name='twitter:card' content='summary' />
		<meta property='og:url' content='<?php echo ml_canonical_permalink(); ?>' />
		<meta property='og:title' content='<?php echo ml_get_title(); ?>' />
		<meta property='og:description' content='<?php echo $description; ?>' />
		<meta property='og:image' content='<?php echo $image; ?>' />
	<?php endif; ?>
	<title><?php echo ml_get_title(); ?></title>
	<link rel='micropub' href='<?php echo ml_base_url() . 'routes/micropub.php'; ?>' />
	<link rel='authorization_endpoint' href='<?php echo Config::INDIEAUTH_PROVIDER; ?>' />
	<link rel='token_endpoint' href='<?php echo Config::INDIEAUTH_TOKEN_ENDPOINT; ?>' />
	<link rel='icon' href='<?php echo $image; ?>'>
	<link rel='apple-touch-icon-precomposed' href='<?php echo $image; ?>'>
	<link rel='canonical' href='<?php echo ml_canonical_permalink(); ?>' />
	<?php
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
	return ml_canonical_permalink() . 'page=' . $pagination;
}

function ml_pagination_right_link () {
	global $pagination;
	return ml_canonical_permalink() . 'page=' . ($pagination + 2);
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

function ml_generate_token () {
	if (function_exists('random_bytes')) {
		return bin2hex(random_bytes(32));
	} elseif (function_exists('openssl_random_pseudo_bytes')) {
		return bin2hex(openssl_random_pseudo_bytes(32));
	} elseif (function_exists('mcrypt_create_iv')) {
		return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
	} else {
		// Not recommended, but if none of the above functions
		// exist, well then...  ¯\_(ツ)_/¯
		return md5(uniqid(rand(), true)) . md5(uniqid(rand(), true));
	}
}

function ml_validate_token () {
	// Create the session if one does not exist already
	if (session_id() === '') session_start();

	// Use the GET parameters, if they are set...
	$code = isset($_GET['code']) ? $_GET['code'] : null;
	$state = isset($_GET['state']) ? $_GET['state'] : null;

	function error ($reason) {
		return [false, $reason];
	}

	// Make sure both code, state, AND session set state are provided
	if ($code === null || $code === '') return error('Provide `code`');
	if ($state === null || $state === '') return error('Provide `state`');
	if (empty($_SESSION['state'])) return error('State not previously set. Try logging in again.');

	// Make sure both states match
	if (!hash_equals($_SESSION['state'], $state)) return error('States do not match. Cannot proceed.');

	// Knowing everything is set, make a request to the token endpoint
	$response = ml_http_request(Config::INDIEAUTH_TOKEN_ENDPOINT, HTTPMethod::POST, [
		'code' => $code,
		'redirect_uri' => ml_base_url() . 'routes/authcallback.php',
		'client_id' => ml_base_url(),
	]);

	$me = $response['me'];

	if (!empty($me)) {
		if ($me === ml_base_url()) {
			$_SESSION['access_token'] = $code;
			return [true, []];
		} else {
			return error('Host URL not correct ("' . $me . '" !== "' . ml_base_url() . '")');
		}
	} else {
		return error(implode('; ', $response));
	}
}
