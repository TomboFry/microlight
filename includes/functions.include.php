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
$posts = null;

/**
 * @param string $key
 * @return bool
 */
function ml_get_not_blank ($key) {
	return (isset($_GET[$key]) && $_GET[$key] !== '');
}

/**
 * @param string $key
 * @return bool
 */
function ml_post_not_blank ($key) {
	return (isset($_POST[$key]) && $_POST[$key] !== '');
}

/**
 * Determines what should be fetched from the DB and is shown to the user.
 *
 * @global string $post_slug
 * @global string $post_tag
 * @global string $post_type
 * @global integer $pagination
 * @global string $search_query
 * @global Show $showing
 * @throws Exception
 */
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

/**
 * Sets up the database and fetches the current user
 *
 * @global DB $db
 * @throws DBError
 */
function ml_database_setup () {
	global $db;

	// Set up a connection to the database
	$db = new DB();
}

/**
 * Load posts from the database, where the results depend on whether any search
 * query or tags/slugs are specified.
 *
 * @global DB $db
 * @global string $post_slug
 * @global string $post_tag
 * @global string $post_type
 * @global string $search_query
 * @global integer $post_total_count
 * @global integer|null $pagination
 * @global Show $showing
 * @global Post|Post[] $posts
 * @throws DBError
 */
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

	$where = [
		// ALWAYS ONLY show public posts
		[
			'column' => 'public',
			'operator' => SQLOP::EQUAL,
			'value' => 1,
			'escape' => SQLEscape::NONE,
		]
	];

	$limit = Config::POSTS_PER_PAGE;
	$offset = Config::POSTS_PER_PAGE * $pagination;

	if ($post_slug !== '') {
		$limit = 1;
		$offset = 0;
		array_push($where, [
			'column' => 'slug',
			'operator' => SQLOP::EQUAL,
			'value' => $post_slug,
			'escape' => SQLEscape::SLUG,
		]);
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
				'column' => 'post_type',
				'operator' => SQLOP::EQUAL,
				'value' => $post_type,
				'escape' => SQLEscape::POST_TYPE,
			]);
		}
	} elseif ($search_query !== '') {
		array_push($where, [
			'column' => 'title',
			'operator' => SQLOP::LIKE,
			'value' => "%$search_query%",
			'escape' => SQLEscape::NONE,
		]);
	} else {
		array_push($where, [
			'column' => 'post_type',
			'operator' => SQLOP::IN,
			'value' => Config::HOMEPAGE_POST_TYPES,
			'escape' => SQLEscape::POST_TYPE,
		]);
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

/**
 * Close DB connection
 *
 * @global DB $db
 */
function ml_database_close () {
	global $db;
	$db->close();
}

/**
 * Returns the title, depending on whether you're on a single post or not.
 *
 * @global Show $showing
 * @global Post|Post[] $posts
 * @return string
 */
function ml_get_title () {
	global $showing;
	global $posts;

	$str = '';

	if ($showing === Show::POST || $showing === Show::PAGE) {
		$str .= $posts->title !== ''
			? $posts->title
			: $posts->summary;
		$str .= Config::TITLE_SEPARATOR;
	}

	$str .= User::NAME;
	return $str;
}

/**
 * Returns the full URL, including 'http(s)'
 *
 * @return string
 */
function ml_base_url () {
	return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . Config::ROOT;
}

/**
 * Returns an absolute URL linking to a self-portrait
 *
 * @return string
 */
function ml_icon_url () {
	return ml_base_url() . 'uploads/me.jpg';
}

/**
 * Returns an absolute URL to a specific post
 *
 * @param string $slug
 * @return string
 */
function ml_post_permalink ($slug) {
	return ml_base_url() . '?post_slug=' . urlencode($slug);
}

/**
 * Returns an absolute URL to the archive of a specific tag
 *
 * @param string $tag
 * @return string
 */
function ml_tag_permalink ($tag) {
	return ml_base_url() . '?post_tag=' . urlencode($tag);
}

/**
 * Returns an absolute URL to the archive of a specific post type
 *
 * @param string $post_type
 * @return string
 */
function ml_type_permalink ($post_type) {
	return ml_base_url() . '?post_type=' . urlencode($post_type);
}

/**
 * Return an absolute, consistent URL that will point to the "official" URL for
 * the page currently being viewed
 * (see: https://en.wikipedia.org/wiki/Canonical_link_element)
 *
 * @global string $post_slug
 * @global string $post_tag
 * @global string $post_type
 * @global string $search_query
 * @return string
 */
function ml_canonical_permalink ($suffix = '') {
	global $post_slug;
	global $post_tag;
	global $post_type;
	global $search_query;

	$usedQuery = false;

	if ($search_query !== '') {
		$str = ml_base_url() . '?search_query=' . urlencode($search_query);
		$usedQuery = true;
	} elseif ($post_tag !== '' || $post_type !== '') {
		$str = ml_base_url() . '?';
		$acc = [];
		if ($post_tag !== '') array_push($acc, 'post_tag=' . urlencode($post_tag));
		if ($post_type !== '') array_push($acc, 'post_type=' . urlencode($post_type));
		$str .= implode('&', $acc);
		$usedQuery = true;
	} elseif ($post_slug !== '') {
		$str = ml_base_url() . '?post_slug=' . urlencode($post_slug);
		$usedQuery = true;
	} else {
		$str = ml_base_url();
	}

	if (!empty($suffix)) {
		if ($usedQuery === false) {
			$str .= '?';
		} else {
			$str .= '&';
		}
		$str .= $suffix;
	}

	return $str;
}

/**
 * Returns an absolute URL pointing towards the directory of the currently
 * selected theme
 *
 * @return string
 */
function ml_get_theme_dir () {
	return ml_base_url() . 'themes/' . Config::THEME;
}

/**
 * Prints an ISO8601 date in the pretty format defined in the configuration
 *
 * @param string $date ISO8601 date
 * @return false|string
 */
function ml_date_pretty ($date) {
	return date(
		Config::DATE_PRETTY,
		strtotime($date)
	);
}

/**
 * Add headers to `<head />` tag in theme (highly recommended to use)
 *
 * @global Show $showing
 * @global Post|Post[] $posts
 */
function ml_page_headers () {
	global $showing;
	global $posts;

	if ($showing === Show::PAGE || $showing === Show::POST) {
		$description = $posts->summary;
		if ($posts->post_type === 'photo') {
			$image = $posts->url;
		} else {
			$image = ml_icon_url();
		}
	} else {
		$description = User::NOTE;
		$image = ml_icon_url();
	}
	?>
	<meta name='description' content='<?php echo $description; ?>' />
	<meta name='generator' content='Microlight <?php echo MICROLIGHT; ?>' />
	<meta name='author' content='<?php echo User::NAME; ?>' />
	<meta name='referrer' content='origin'>
	<?php if (Config::OPEN_GRAPH === true): ?>
		<meta name='twitter:card' content='summary' />
		<meta property='og:url' content='<?php echo ml_canonical_permalink(); ?>' />
		<meta property='og:title' content='<?php echo ml_get_title(); ?>' />
		<meta property='og:description' content='<?php echo $description; ?>' />
		<meta property='og:image' content='<?php echo $image; ?>' />
	<?php endif; ?>
	<title><?php echo ml_get_title(); ?></title>
	<link rel='micropub' href='<?php echo ml_base_url() . 'micropub/index.php'; ?>' />
	<link rel='authorization_endpoint' href='<?php echo Config::INDIEAUTH_PROVIDER; ?>' />
	<link rel='token_endpoint' href='<?php echo Config::INDIEAUTH_TOKEN_ENDPOINT; ?>' />
	<link rel='icon' href='<?php echo $image; ?>'>
	<link rel='apple-touch-icon-precomposed' href='<?php echo $image; ?>'>
	<link rel='canonical' href='<?php echo ml_canonical_permalink(); ?>' />
	<?php
}

/**
 * Determines whether pagination buttons should even be shown at all, based on
 * the type of page we're viewing.
 *
 * @global Show $showing
 * @global integer $post_total_count
 * @return bool
 */
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

/**
 * Determines whether we should show the previous page button or not
 *
 * @global integer $pagination
 * @return bool
 */
function ml_pagination_left_enabled () {
	global $pagination;
	return $pagination > 0;
}

/**
 * Determines whether we should show the next page button or not
 *
 * @global integer $pagination
 * @global integer $post_total_count
 * @return bool
 */
function ml_pagination_right_enabled () {
	global $pagination;
	global $post_total_count;
	$total = ceil($post_total_count / Config::POSTS_PER_PAGE) - 1;
	return $pagination < $total;
}

/**
 * Returns an absolute URL to the previous page
 *
 * @global integer $pagination
 * @return string
 */
function ml_pagination_left_link () {
	global $pagination;
	return ml_canonical_permalink('page=' . $pagination);
}

/**
 * Returns an absolute URL to the next page
 *
 * @global integer $pagination
 * @return string
 */
function ml_pagination_right_link () {
	global $pagination;
	return ml_canonical_permalink('page=' . ($pagination + 2));
}

/**
 * Determines whether a location from the database are coordinates or an
 * address. If the former, they will be separated from the string into an array
 * containing both `lat` and `long`, otherwise the original string will be
 * returned.
 *
 * @param string|null $location
 * @return string[]|string|null
 */
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

/**
 * Determines whether a post has a name or not
 *
 * @param Post $post
 * @return bool
 */
function ml_post_has_title ($post) {
	return $post->title !== null && $post->title !== '';
}

/**
 * Generates a token to be used for both CSRF and authentication state
 *
 * @throws Exception
 * @return string Randomly generated token
 */
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

/**
 * Used to validate the authentication request, using the code and state from
 * the GET parameters.
 *
 * @throws Exception
 * @return array Where the contents are [success => bool, string[] => errors]
 */
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
			unset($_SESSION['state']);
			return [true, []];
		} else {
			return error('Host URL not correct ("' . $me . '" !== "' . ml_base_url() . '")');
		}
	} else {
		return error(implode('; ', $response));
	}
}
