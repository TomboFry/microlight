<?php

if (!defined('MICROLIGHT')) die();

require_once('includes/lib/media.php');
require_once('includes/webmention.include.php');

/**
 * Inserts a post into the database, returning any errors if they occur,
 * otherwise returning a 201 CREATED response to the new post.
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
		while ($existing > 0) {
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

			if ($suffix > 50) {
				throw new Exception('This slug is used by too many slugs');
			}
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
function post_create_entry (PostEntry $entry){
	$new_post = post_create_post($entry);

	try {
		$final_slug = insert_post($new_post);
	} catch (\Throwable $error) {
		ml_http_error(HTTPStatus::SERVER_ERROR, $error->getMessage());
		return;
	}

	if (should_perform_webmention($new_post) === true) {
		try {
			ml_webmention_perform($new_post['url'], $final_slug);
		} catch (\Throwable $error) {
			// This error is not critical, as such, so a failing webmention does
			// not really warrant it to be handled as such, hence the simple
			// error logging.
			error_log('Could not perform webmention. Here is why:');
			error_log('Code: ' . $error->getCode());
			error_log('Message: ' . $error->getMessage());
		}
	}

	return;
}
