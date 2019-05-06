<?php

if (!defined('MICROLIGHT')) die();

require_once('includes/api.include.php');

class PostEntry {
	public $name;
	public $summary;
	public $content;
	public $published;
	public $category;
	public $photo;
	public $bookmark_of;
	public $in_reply_to;
	public $like_of;
	public $repost_of;
	public $mp_slug;

	function __construct ($is_json = false) {
		if ($is_json) {
			$this->parse_json();
		} else {
			$this->parse_form();
		}
	}

	/**
	 * Parses the body of a form encoded request
	 *
	 * @return void
	 */
	function parse_form () {
		if (ml_api_post('h') !== 'entry') {
			throw new Exception('h must equal entry (for now)');
		}

		$this->name = ml_api_post('name');
		$this->summary = ml_api_post('summary');
		$this->content = ml_api_post('content');
		$this->published = ml_api_post('published');
		$this->category = ml_api_post('category');
		$this->photo = ml_api_post('photo');
		$this->bookmark_of = ml_api_post('bookmark-of');
		$this->in_reply_to = ml_api_post('in-reply-to');
		$this->like_of = ml_api_post('like-of');
		$this->repost_of = ml_api_post('repost-of');
		$this->mp_slug = ml_api_post('mp-slug');
	}

	/**
	 * Parses the body of a JSON encoded request
	 *
	 * @return void
	 * @throws Exception
	 */
	function parse_json () {
		global $post;

		$type = ml_api_post_json($post, 'type', true);
		if ($type !== 'h-entry') {
			throw new Exception('`type` must equal `h-entry` for now.');
		}

		// Get all post properties from within properties
		$props = ml_api_post_json($post, 'properties', false);

		// Get all easy values here
		$this->name = ml_api_post_json($props, 'name', true);
		$this->summary = ml_api_post_json($props, 'summary', true);
		$this->published = ml_api_post_json($props, 'published', true);
		$this->category = ml_api_post_json($props, 'category', false);
		$this->photo = ml_api_post_json($props, 'photo', true);
		$this->bookmark_of = ml_api_post_json($props, 'bookmark-of', true);
		$this->in_reply_to = ml_api_post_json($props, 'in-reply-to', true);
		$this->like_of = ml_api_post_json($props, 'like-of', true);
		$this->repost_of = ml_api_post_json($props, 'repost-of', true);
		$this->mp_slug = ml_api_post_json($props, 'mp-slug', true);

		// Parse content - May either be in text form or HTML, so figure it out
		$content = ml_api_post_json($props, 'content', true);

		// We know that if the single element in $content is an array, it
		// probably has the `html` or `value` keys inside
		if (is_array($content)) {
			$html = ml_api_post_json($content, 'html', false);
			$value = ml_api_post_json($content, 'value', false);

			if ($html !== null) {
				$this->content = $html;
			} else if ($value !== null) {
				$this->content = $value;
			} else {
				// Couldn't find content?
				$this->content = '';
			}
		} else {
			$this->content = $content;
		}
	}
}
