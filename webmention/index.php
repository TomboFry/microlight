<?php

define('MICROLIGHT', 'v0.0.1');

chdir('..');
require_once('includes/config.php');
require_once('includes/api.include.php');

if (ml_api_method() !== HTTPMethod::POST) {
	ml_http_response(HTTPStatus::METHOD_NOT_ALLOWED);
	return;
}

// Initialise POST
if (!ml_api_post_decode()) return;

/**
 * Ensure that the provided URL is a fully valid, absolute URL
 *
 * @param string $url
 * @return boolean
 */
function ml_webmention_validate_url ($url) {
	$url_parts = parse_url($url);

	// URL must be absolute, so check for a scheme (http(s)) and a hostname/IP
	if (!isset($url_parts['scheme']) || !isset($url_parts['host'])) return false;

	// Must be a HTTP URL, do not allow `mailto` or `ftp`, etc.
	if ($url_parts['scheme'] !== 'http' && $url_parts['scheme'] !== 'https') {
		return false;
	}

	// According to the webmention spec, should not contain localhost or a
	// loopback address
	// if ($url_parts['host'] === 'localhost' || substr($url_parts['host'], 0, 4) === '127.') {
	// 	return false;
	// }

	// Validate the URL we've created to make sure it's valid
	if (filter_var($url, FILTER_VALIDATE_URL) === false) return false;

	// If both tests passed, return true.
	return true;
}

/**
 * Validate the POST body of the incoming request. Validates two URLs according
 * to the following link:
 * https://www.w3.org/TR/webmention/#request-verification
 *
 * @global array $post
 * @return void
 */
function ml_webmention_validate_post () {
	global $post;

	// Make sure both `source` and `target` URLs are provided
	if (!isset($post['source'])) return 'Source URL was not provided';
	if (!isset($post['target'])) return 'Target URL was not provided';

	// Make sure both URLs are not the same
	if ($post['source'] === $post['target']) {
		return 'Source and Target URLs cannot be the same';
	}

	// Make sure provided URLs *are actually* URLs.
	if (ml_webmention_validate_url($post['source']) === false) {
		return 'Source URL is not a valid URL';
	}
	if (ml_webmention_validate_url($post['target']) === false) {
		return 'Target URL is not a valid URL';
	}

	return true;
}

/**
 * Make sure the target URL provided by the request is actually an entry that
 * exists in the system. Loads data from the database.
 *
 * @param DB $db The database connection
 * @param string $target Target URL, should be provided by POST body
 * @return void
 * @throws Exception
 */
function ml_webmention_validate_target ($db, $target) {
	// May throw if the URL does not match
	$slug = ml_slug_from_url($target);

	// Attempt to fetch the post from the database
	$post_db = new Post($db);
	$post_where = [ SQL::where_create('slug', $slug, SQLOP::EQUAL, SQLEscape::SLUG) ];
	$post = $post_db->find_one($post_where);

	// Validate post
	if ($post === null) throw new Exception('Target post does not exist');
	if ($post['status'] === 'deleted') throw new Exception('Target post has been deleted');

	return $post['id'];
}

/**
 * Helper function to generate part of an XPath query to match an element's
 * class name
 *
 * @param string $class_name
 * @return string
 */
function xpath_class ($class_name) {
	return 'contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")';
}

/**
 * Perform a GET request on the source URL to try and find the Target URL within
 * the entry somewhere.
 *
 * @param string $source Source URL
 * @param string $target Target URL
 * @return array Post's details
 * @throws Exception
 */
function ml_webmention_validate_source_contents ($source, $target) {
	// Load the page, including the headers, but specifically setting the user-agent
	$response = ml_http_request($source, HTTPMethod::GET, null, [
		'User-Agent: Microlight/' . MICROLIGHT . ' (webmention)'
	]);

	// For multiple reuse, this is the structure of a "deleted" webmention.
	$deleted = [
		'author' => false,
		'deleted' => true,
	];

	// Make sure the webpage returned a successful response, unless it has been deleted.
	if ($response['code'] === 410) {
		return $deleted;
	} elseif ($response['code'] < 200 || $response['code'] > 299) {
		throw new Exception('Source URL returned non-success status code');
	}

	// Parse the document
	$doc = new DOMDocument();
	@$doc->loadHTML($response['body']);
	$xpath = new DOMXPath($doc);

	// Perform an XPath query with the following expression.
	// This selects any <a/> tags that contain the target URL in their href
	// attribute.
	$target_links = $xpath->query('//a[@href="' . $target . '"]');

	// Make sure there is at least one link on the page with an exactly matching
	// target URL.
	if ($target_links->length < 1) return $deleted;

	// If the page is indeed valid, determine the:
	// * entry type
	// * post content
	// * published date
	// * author details (name, image, home URL)

	// First, get the entry containers and use the first one.
	$entry = $xpath->query('//*[' . xpath_class('h-entry') . ']');
	if ($entry->length < 1) {
		throw new Exception('Source URL does not contain a h-entry container');
	}
	$entry = $entry[0];

	// Determine the published date of the entry
	$published = $xpath->query('.//*[' . xpath_class("dt-published") . ']', $entry);
	if ($published->length < 1) {
		throw new Exception('Source URL does not contain a published date');
	}
	if ($published[0]->hasAttribute('datetime')) {
		$published = $published[0]->getAttribute('datetime');
	} else {
		$published = trim(strip_tags($published[0]->textContent));
	}

	// Determine author card details
	$author_query = './/*[';
	$author_query .= xpath_class("p-author") . ' or ';
	$author_query .= xpath_class("h-card") . ' or ';
	$author_query .= xpath_class("hcard") . ' or ';
	$author_query .= xpath_class("vcard") . ']';
	$author = $xpath->query($author_query, $entry);
	if ($author->length < 1) {
		throw new Exception('Source URL does not contain an author card within the post');
	}
	$author = $author[0];

	// With the author h-card, get the image, name, and URL
	$images = $xpath->query('.//img[' . xpath_class("u-photo") . ']', $author);
	$author_image = '/uploads/me.jpg';
	foreach ($images as $image) {
		if ($image->hasAttribute('src')) {
			$author_image = $image->getAttribute('src');
			break;
		}
	}

	$names = $xpath->query('.//*[' . xpath_class("p-name") . ']', $author);
	$author_name = '';
	if ($names->length > 0) {
		$author_name = trim(strip_tags($names[0]->textContent));
	}

	$urls = $xpath->query('.//a[' . xpath_class("u-url") . ']', $author);
	if ($urls->length < 1) {
		throw new Exception('Source URL does not contain the author\'s home URL');
	}
	$author_url = $urls[0]->getAttribute('href');

	// Assume normal post type for the meantime.

	// Determine the post's content
	// There may be no content if the post type is `like`, `repost`, or `bookmark`
	$content_query = './/*[';
	$content_query .= xpath_class("e-content") . ' or ';
	$content_query .= xpath_class("p-content") . ']';
	$content_dom = $xpath->query($content_query, $entry);
	$content = '';
	if ($content_dom->length > 0) {
		$content = trim(strip_tags($content_dom[0]->textContent));
	}

	return [
		'author' => [
			'name' => $author_name,
			'url' => $author_url,
			'photo_url' => $author_image,
		],
		'datetime' => $published,
		'contents' => $content,
		'deleted' => false,
	];
}

/**
 * Match the discovered author with one from the database. If one is not found,
 * insert it and return the new ID. If one does exist, update any fields that
 * have changed and return the existing ID.
 *
 * @param DB $db Database connection
 * @param array $author Array/object containing source's author details
 * @param string $author['name'] Author's name
 * @param string $author['url'] Author's URL
 * @param string $author['photo_url'] Author's Photo URL
 * @return array Contains same details but with a Person ID from the database
 */
function ml_webmention_validate_author ($db, $author) {
	$person_db = new Person($db);

	// The author may be false if the post was deleted, in which case return early.
	if ($author === false) return false;

	// Find a person
	$where = [ SQL::where_create('url', $author['url']) ];
	$person = $person_db->find_one($where);

	// If they don't exist, insert their details and
	// associate the new ID with the author
	if ($person === null) {
		$person_id = $person_db->insert($author);
		$author['id'] = $person_id;
		return $author;

	// Otherwise, update their details and return the updated cache
	} else {
		// Keep track of any changes
		$changes = [];

		// Update Photo URL
		if ($person['photo_url'] !== $author['photo_url']) {
			$changes['photo_url'] = $author['photo_url'];
		}

		// Update Name
		if ($person['name'] !== $author['name']) {
			$changes['name'] = $author['name'];
		}

		// Only update if necessary
		if (count($changes) === 0) return $person;

		// Make the changes
		$update_where = [ SQL::where_create(
			'id',
			$person['id'],
			SQLOP::EQUAL,
			SQLEscape::INT
		) ];
		$person_db->update($changes, $update_where);

		// If all went well, just make sure the Person ID is included
		return $person;
	}
}

/**
 * Store the new interaction in the database. Everything at this point should be
 * correctly validated and ready to go straight in!
 *
 * @param DB $db
 * @param integer $post_id
 * @param array $post_details
 * @param array $author
 * @return int
 * @throws DBError
 */
function ml_webmention_interaction_store ($db, $source, $post_id, $post_details, $author) {
	$interaction_db = new Interaction($db);

	// See if this URL has already been entered into the database
	$where = [ SQL::where_create('url', $source) ];
	$existing = $interaction_db->find_one($where);

	// Delete and return early, only if the post has been deleted and the
	// interaction *does* actually exist.
	if ($post_details['deleted'] === true && $existing !== null) {
		$interaction_db->delete($where);
		return;
	}

	$interaction_properties = [
		'type' => 'reply',
		'datetime' => $post_details['datetime'],
		'contents' => $post_details['contents'],
		'url' => $source,
		'person_id' => $author['id'],
		'post_id' => $post_id,
	];

	// Update the existing record, or create a new one.
	if ($existing !== null) {
		$interaction_db->update($interaction_properties, $where);
		return $existing['id'];
	} else {
		$interaction_id = $interaction_db->insert($interaction_properties);
	}

	return $interaction_id;
}

// TODO: Determine entry type on source URL
// TODO: Limit fetching source contents to 5 seconds or 1 MB (whichever comes first)

try {
	// Step 1: Validate input, can't proceed if the URLs aren't set up properly!
	$post_validation = ml_webmention_validate_post();
	if ($post_validation !== true) throw new Exception($post_validation);

	// Easier variable names to type :D
	$source = $post['source'];
	$target = $post['target'];

	$db = new DB();

	// Step 2: Make sure the post actually exists before adding a comment to it
	$post_id = ml_webmention_validate_target($db, $target);

	// Step 3: Fetch source contents to insert into database
	$post_details = ml_webmention_validate_source_contents($source, $target);

	// Step 4: See if the post's author is already in the database, adding them
	//         if not.
	$author = ml_webmention_validate_author($db, $post_details['author']);

	// Step 5: Finally, store the webmention in the database, updating or
	//         deleting the interaction if it has changed.
	$interaction_id = ml_webmention_interaction_store(
		$db,
		$source,
		$post_id,
		$post_details,
		$author
	);

	ml_http_response(HTTPStatus::OK);
	return;
} catch (\Throwable $err) {
	ml_http_error(HTTPStatus::INVALID_REQUEST, $err->getMessage());
	return;
}
