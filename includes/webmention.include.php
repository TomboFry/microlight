<?php

if (!defined('MICROLIGHT')) die();

require_once('sql.include.php');

/**
 * Ensure that the webmention URL found is actually a valid absolute URL, and if
 * not, perhaps the source URL can help.
 * @param string $source
 * @param string $webmention_url
 * @return string|false
 */
function ml_webmention_validate_url ($source, $url) {
	$output = '';
	$url_parts = parse_url($url);
	$source_parts = parse_url($source);

	// Test for absolute URL. As long as http(s) and the hostname is provided,
	// we can safely assume it is absolute, as the rest doesn't really matter.
	if (isset($url_parts['scheme']) && isset($url_parts['host'])) {
		$output = $url;
	
	// Otherwise, the provided URL must be relative
	// (ie. not contain a scheme or hostname)
	} else if (!isset($url_parts['host'])) {
		$output = $source_parts['scheme'] . '://' . $source_parts['host'];

		// Add source port
		if (isset($source_parts['port'])) $output .= ':' . $source_parts['port'];
		
		// Relative to Root (because first character is '/')
		if (strpos($url_parts['path'], '/') === 0) {
			$output .= $url;

		// Relative to source page
		} else {
			$output .= $source_parts['path'];
			// Prevent the URL from having two slashes if the path already ends with slash
			if ($source_parts['path'][-1] !== '/' && strlen($url) > 0) $output .= '/';
			$output .= $url;
		}
	}

	// Finally, validate the URL we've created to make sure it's valid
	if (filter_var($output, FILTER_VALIDATE_URL) !== false) return $output;

	return false;
}

/**
 * Find a webmention URL by looking at the HTTP headers from the source URL. If
 * no URL could be found, return `false`.  
 * Example: `Link: <https://.../>; rel="webmention"`
 * @param string $url
 * @return string|false
 */
function ml_webmention_head ($url) {
	// Get HTTP Headers from this URL
	$response = ml_http_request($url, HTTPMethod::HEAD, null, [
		'User-Agent: Microlight/' . MICROLIGHT . ' (webmention)'
	]);

	// Split headers by new lines
	$response = explode("\r\n", trim($response));

	for ($i = 0; $i < count($response); $i++) {
		// Split headers like `Content-Type: application/json`
		// into `[ 'Content-Type', 'application/json' ];`
		$response[$i] = explode(': ', $response[$i]);
		
		// Skip headers that don't have a key followed by a value
		if (count($response[$i]) <= 1) continue;

		$header = $response[$i][0];
		$value = $response[$i][1];

		// We only care about `Link` headers
		if (strtolower($header) !== 'link') continue;

		// And it must be a webmention link, too.
		if (strpos($value, 'rel="webmention"') === false) continue;

		// Extract the URL from between the angled brackets
		if (!preg_match('/^\<(.*)\>; .*/', $value, $match)) continue;

		// We've found it!
		return $match[1];
	}

	return false;
}

/**
 * Find a webmention URL by looking at the HTML returned from the source URL. If
 * no URL could be found, return `false`.  
 * Example: `<link rel='webmention' href='https://.../' />`
 * @param string $url
 * @return string|false
 */
function ml_webmention_html ($url) {
	// Download the site's HTML
	$response = ml_http_request($url, HTTPMethod::GET, null, [
		'User-Agent: Microlight/' . MICROLIGHT . ' (webmention)'
	]);

	// Parse the document
	$doc = new DOMDocument();
	$doc->loadHTML($response);

	// Get all `link` tags
	$links = $doc->getElementsByTagName('link');
	$ahrefs = $doc->getElementsByTagName('a');

	// Search for the link in all <link/> tags
	$webmention_url = null;
	foreach ($links as $link) {
		$rels = explode(' ', $link->getAttribute('rel'));
		if (in_array('webmention', $rels, true)) {
			$webmention_url = $link->getAttribute('href');
			break;
		}
	}

	// Do the same for <a/> tags, only if there wasn't one found
	// in the <link/> tags.
	if ($webmention_url === null) {
		foreach ($ahrefs as $link) {
			$rels = explode(' ', $link->getAttribute('rel'));
			if (in_array('webmention', $rels, true)) {
				$webmention_url = $link->getAttribute('href');
				break;
			}
		}
	}

	// Return false if there is no URL.
	if ($webmention_url !== null) return $webmention_url;
	return false;
}

/**
 * Perform a webmention to the specified URL
 * @param string $url Target URL
 * @param string $post_slug Slug of the newly created post
 * @throws Exception
 * @return void
 */
function ml_webmention_perform ($url, $post_slug) {
	// Check if post with slug exists first. It *should* exist, as this function
	// will likely be run just after the post is created, but nonetheless, check
	// anyway.
	$db = new DB();
	$post = new Post($db);
	$post_exists = $post->count([ SQL::where_create('slug', $post_slug, SQLOP::EQUAL, SQLEscape::SLUG) ]);
	if ($post_exists < 1) throw new Exception('Post with slug `' . $post_slug . '` does not exist');

	// Try HEAD first
	$webmention_url = ml_webmention_head($url);

	// Attempt HTML afterwards
	if ($webmention_url === false) $webmention_url = ml_webmention_html($url);

	// If there's no webmention link, just return as successful
	if ($webmention_url === false) return;

	// Parse relative URLs before attempting to send a webmention
	$webmention_url = ml_webmention_validate_url($url, $webmention_url);
	if ($webmention_url === false) throw new Exception('Invalid webmention URL: "' . $webmention_url . '"');

	// TODO: Get headers and return code off request made, to determine whether
	// successful.
	$response = ml_http_request($webmention_url, HTTPMethod::POST, [
		'source' => ml_post_permalink($post_slug),
		'target' => $url,
	]);
	// if ($response === ?) {...}

	return;
}
