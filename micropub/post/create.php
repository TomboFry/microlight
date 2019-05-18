<?php

if (!defined('MICROLIGHT')) die();

require_once('includes/lib/media.php');
require_once('includes/webmention.include.php');

/**
 * Validates the `published` or `updated` field
 *
 * @param string|null $date The date at which the post should be published/updated
 * @return string|false Return the date in ISO8601 if valid, otherwise false
 */
function validate_date ($date) {
	if ($date === null) {
		// Use the current timestamp, if not provided.
		return gmdate("c");
	} else {
		$date = strtotime($date);
		if ($date !== false) return date('c', $date);
		return false;
	}
}

/**
 * If not provided, the summary will be taken from the first 160 characters of
 * the actual content. It is assumed that `content` has already been validated.
 *
 * @param string|null $summary
 * @param string $content
 * @return string
 */
function validate_summary ($summary, $content) {
	if (empty($summary)) {
		// Summary should be a snippet from the content, limited to 160 chars.
		// It's 157 here because if it reaches that value we will append an
		// ellipsis to bring it to the total 160.
		$summary = strip_tags($content);
		$summary = substr($summary, 0, 157);
		$summary = preg_replace('/\s+/', ' ', $summary);
		if (strlen($summary) === 157) $summary .= "...";
	}

	return strip_tags($summary);
}

/**
 * Ensures that every category provided is a valid alphanumeric string,
 * otherwise returning false.
 *
 * @param string[] $category
 * @return string[]|false
 */
function validate_category ($category) {
	if (empty($category)) return [];
	if (is_array($category) === false) {
		if (is_string($category)) {
			$category = [ $category ];
		} else {
			return false;
		}
	};

	foreach ($category as $key => $value) {
		if (!mb_check_encoding($value, 'ASCII')) return false;
		if (!preg_match('/^[a-zA-Z0-9_\- ]+$/', $value)) return false;
	}

	return $category;
}

/**
 * Generates a slug based on either the name (if provided), or the post's
 * summary.
 *
 * @param string|null $name The post's currently working name
 * @param string $summary Post's summary, assumed to already be validated
 * @return string The final slug to be used for this post
 */
function generate_slug ($name, $summary) {
	if (empty($name)) {
		// Take the first 10 words from the summary
		return slugify(implode('-', array_slice(preg_split('/\s/m', $summary), 0, 10)));
	}

	// Alternatively, if the name is already populated, slugify it.
	return slugify($name);
}

/**
 * Determines the post type depending on whether other optional fields to POST
 * were provided.
 * 
 * @param PostEntry $entry
 * @return array|false If a valid post type was detected, an array containing
 *                     "type" and "url" keys, otherwise false.
 */
function validate_post_type ($entry) {
	if (!empty($entry->photo) && filter_var($entry->photo, FILTER_VALIDATE_URL) !== false) {
		return [
			'type' => 'photo',
			'url' => $entry->photo,
			'webmention' => false,
		];
	}

	// Perhaps an image was uploaded in the request itself
	if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
		$photo = new ImageResizer($_FILES['photo']);

		return [
			'type' => 'photo',
			'url' => $photo->get_permalink(),
			'webmention' => false,
		];
	}

	if (!empty($entry->bookmark_of)) {
		return [
			'type' => 'bookmark',
			'url' => $entry->bookmark_of,
			'webmention' => false,
		];
	}

	// The following post types should perform webmentions

	if (!empty($entry->in_reply_to)) {
		return [
			'type' => 'reply',
			'url' => $entry->in_reply_to,
			'webmention' => true,
		];
	}

	if (!empty($entry->like_of)) {
		return [
			'type' => 'like',
			'url' => $entry->like_of,
			'webmention' => true,
		];
	}

	if (!empty($entry->repost_of)) {
		return [
			'type' => 'repost',
			'url' => $entry->repost_of,
			'webmention' => true,
		];
	}

	return false;
}

/**
 * Inserts a post into the database, returning any errors if they occur,
 * otherwise returning a 201 CREATED response to the new post.
 *
 * @param array $post
 * @return string Post's final slug
 * @throws Exception
 */
function insert_post ($post) {
	$slug = $post['slug'];

	try {
		$db = new DB();
		$db_post = new Post($db);
		
		$existing = $db_post->count([
			[
				'column' => 'slug',
				'operator' => SQLOP::EQUAL,
				'value' => $slug,
				'escape' => SQLEscape::SLUG,
			],
		]);

		// Loop through suffixed slugs until one doesn't exist, or until we've
		// tried 50 times, in which case return an error.
		$suffix = 1;
		while ($existing > 0 || $suffix > 50) {
			$new_slug = $slug . '-' . $suffix;

			$existing = $db_post->count([
				[
					'column' => 'slug',
					'operator' => SQLOP::EQUAL,
					'value' => $new_slug,
					'escape' => SQLEscape::SLUG,
				],
			]);

			// Overwrite the existing slug with the new slug
			$post['slug'] = $new_slug;
			$suffix += 1;
		}

		if ($suffix > 50) {
			throw new Exception('This slug is used by too many slugs');
		}

		$postId = $db_post->insert($post);
		$postId = intval($postId);

		if (is_int($postId) && $postId !== 0) {
			ml_http_response(
				HTTPStatus::CREATED,
				null,
				null,
				ml_post_permalink($post['slug'])
			);
			return $post['slug'];
		} else {
			throw new Exception('Could not create entry. Unknown reason.');
		}
	} catch (DBError $e) {
		error_log('Post could not be inserted...');
		throw new Exception($e->getMessage());
	}
}

/**
 * The main logic for the Micropub `h=entry` request. Takes various POST values
 * and converts them into an object suitable for the microlight database.
 * Any errors will be returned to the user.
 *
 * @param PostEntry $entry
 * @return void
 * @throws Exception
 */
function post_create_entry ($entry) {
	// Internally calculated values
	$post_type = 'article';
	$post_slug = '';
	$post_public = true;
	$post_url = null;
	$perform_webmention = false;

	// VALIDATION / PROCESSING

	if (empty($entry->name)) $post_type = 'note';

	$entry->published = validate_date($entry->published);
	if ($entry->published === false) {
		ml_http_error(
			HTTPStatus::INVALID_REQUEST,
			'Invalid `published` value'
		);
		return;
	}

	$entry->summary = validate_summary($entry->summary, $entry->content);

	if ($entry->mp_slug !== null) {
		$post_slug = $entry->mp_slug;
	} else {
		$post_slug = generate_slug($entry->name, $entry->summary);
	}

	$entry->category = validate_category($entry->category);
	if ($entry->category === false) {
		ml_http_error(
			HTTPStatus::INVALID_REQUEST,
			'Invalid `category` value'
		);
		return;
	}

	// Check for a 'private' category specified.
	// If present, remove it from the categories and make the post invisible to
	// the archive.
	$private_category_key = array_search('private', $entry->category, true);
	if ($private_category_key !== false) {
		array_splice($entry->category, $private_category_key, 1);
		$post_public = false;
	}
	$entry->category = implode(',', $entry->category);
	if (strlen($entry->category) > 0) $entry->category .= ',';

	// Determine post type if `in-reply-to`, `like-of`, `repost-of` or
	// `bookmark-of` URLs are provided.
	$new_post_type = validate_post_type($entry);
	if ($new_post_type !== false) {
		$post_type = $new_post_type['type'];
		$post_url = $new_post_type['url'];
		$perform_webmention = $new_post_type['webmention'];
	}

	$post = [
		'title' => $entry->name,
		'summary' => $entry->summary,
		'content' => $entry->content,
		'post_type' => $post_type,
		'slug' => $post_slug,
		'published' => $entry->published,
		'tags' => $entry->category,
		'public' => $post_public,
		'url' => $post_url
	];

	try {
		$final_slug = insert_post($post);
	} catch (\Throwable $error) {
		ml_http_error(HTTPStatus::SERVER_ERROR, $error->getMessage());
		return;
	}

	if ($perform_webmention === true) {
		try {
			ml_webmention_perform($post_url, $final_slug);
		} catch (\Throwable $error) {
			error_log('Could not perform webmention. Here is why:');
			error_log('Code: ' . $error->getCode());
			error_log('Message: ' . $error->getMessage());
		}
	}

	return;
}
