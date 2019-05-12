<?php

if (!defined('MICROLIGHT')) die();

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
		$header = $response[$i][0];
		$value = $response[$i][1];

		// We only care about `Link` headers
		if (strtolower($header) !== 'link') continue;

		// And it must be a webmention link, too.
		if (strpos($value, 'rel="webmention"') === false) continue;

		// Extract the URL from between the angled brackets
		if (!preg_match('/^\<(.*)\>; .*/', $value, $match)) continue;

		// Make sure it's a valid URL
		if (filter_var($match[1], FILTER_VALIDATE_URL) === false) continue;

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

	// Search for the link
	$webmention_url = null;
	foreach ($links as $link) {
		if ($link->getAttribute('rel') === 'webmention') {
			$webmention_url = $link->getAttribute('href');
			break;
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
 * @return bool Success (or not)
 */
function ml_webmention_perform ($url, $post_slug) {
	// Try HEAD first
	$webmention_link = ml_webmention_head($url);

	// Attempt HTML afterwards
	if ($webmention_link === false) {
		$webmention_link = ml_webmention_html($url);
	}

	// If there's no webmention link, just return as successful
	if ($webmention_link === false) return true;

	// TODO: Perform webmention here
	echo "Performing webmention with URL: `$webmention_link`";
}
