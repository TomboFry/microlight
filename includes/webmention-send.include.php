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
			// Remove everything after the last slash to point to the corrent
			// relative URL.
			$source_path = substr($source_parts['path'], 0, strrpos($source_parts['path'], '/'));

			// Add the new path
			$output .= $source_path;

			// Prevent the URL from having two slashes if the path already ends with slash
			if ($source_path[-1] !== '/' && strlen($url) > 0) $output .= '/';
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
 * @param array $response
 * @return string|false
 */
function ml_webmention_head ($response) {
	foreach ($response['headers'] as $header => $value) {
		// We only care about `Link` headers
		if ($header !== 'link') continue;

		// The link header may contain more than one URL
		$value = explode(',', $value);

		foreach ($value as $link) {
			// Parse link headers using fancy regex
			if (preg_match('/^\s*\<([^\>]*)\>;\s*rel=\"?([^\"]*)\"?\s*$/', $link, $match) !== 1) continue;

			// Indices explained:
			// 0 = original string
			// 1 = URL
			// 2 = rel

			$rels = explode(' ', $match[2]);
			if (!in_array('webmention', $rels, true)) continue;

			// We've found it!
			return $match[1];
		}
	}

	return false;
}

/**
 * Find a webmention URL by looking at the HTML returned from the source URL. If
 * no URL could be found, return `false`.  
 * Example: `<link rel='webmention' href='https://.../' />`
 * @param array $response
 * @return string|false
 */
function ml_webmention_html ($response) {
	// Parse the document
	$doc = new DOMDocument();
	@$doc->loadHTML($response['body']);
	$xpath = new DOMXPath($doc);

	// Perform an XPath query with the following expression.
	// This selects any <link/> and <a/> tags that contain 'webmention' in their
	// rel attribute.
	$query = "//link[contains(@rel,'webmention')]|//a[contains(@rel,'webmention')]";
	$links = $xpath->query($query);

	// Search for the link in all <link/> and <a> tags
	$webmention_url = null;
	foreach ($links as $link) {
		// Despite getting all tags with a rel containing 'webmention', we still
		// need to check whether it is explicitly provided as a single word
		// e.g. 'sneakywebmention' is not valid, but 'sneaky webmention' is.
		$rels = explode(' ', strtolower($link->getAttribute('rel')));
		$has_href = $link->hasAttribute('href');
		if (in_array('webmention', $rels, true) && $has_href === true) {
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

	// Load the page, including the headers, but specifically setting the user-agent
	$webmention_page = ml_http_request($url, HTTPMethod::GET, null, [
		'User-Agent: Microlight/' . MICROLIGHT . ' (webmention)'
	]);

	$webmention_url = ml_webmention_head($webmention_page);

	// Attempt HTML afterwards
	if ($webmention_url === false) $webmention_url = ml_webmention_html($webmention_page);

	// If there's no webmention link, just return as successful
	if ($webmention_url === false) return;

	// Parse relative URLs before attempting to send a webmention
	$webmention_url = ml_webmention_validate_url($url, $webmention_url);
	if ($webmention_url === false) throw new Exception('Invalid webmention URL: "' . $webmention_url . '"');

	$response = ml_http_request($webmention_url, HTTPMethod::POST, [
		'source' => ml_post_permalink($post_slug),
		'target' => $url,
	]);

	// If the webmention server returned a failed request
	if ($response['code'] >= 400) throw new Exception('Error returned: ' . $response['body']);

	error_log($response['body']);
	// if ($response === ?) {...}

	return;
}
