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
	public $audio;
	public $video;
	public $bookmark_of;
	public $in_reply_to;
	public $like_of;
	public $repost_of;
	public $mp_slug;

	/**
	 * Convert a post from the database into a PostEntry
	 * @param array[] $properties
	 * @return void
	 */
	public function parse_entry ($properties) {
		$this->name = $properties['name'];
		$this->summary = $properties['summary'];
		$this->content = $properties['content'];
		$this->published = $properties['published'];
		$this->category = $properties['tags'];
		$this->mp_slug = $properties['slug'];

		switch ($properties['post_type']) {
		case 'photo':
			$this->photo = $properties['url'];
			break;
		case 'audio':
			$this->audio = $properties['url'];
			break;
		case 'video':
			$this->video = $properties['url'];
			break;
		case 'bookmark':
			$this->bookmark_of = $properties['url'];
			break;
		case 'reply':
			$this->in_reply_to = $properties['url'];
			break;
		case 'like':
			$this->like_of = $properties['url'];
			break;
		case 'repost':
			$this->repost_of = $properties['url'];
			break;
		}
	}

	/**
	 * Parse the body of a JSON/form encoded request
	 * @param bool $is_json
	 * @return void
	 */
	public function parse_post (bool $is_json = false) {
		if ($is_json === true) {
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
	private function parse_form () {
		$this->name = ml_api_post('name');
		$this->summary = ml_api_post('summary');
		$this->content = ml_api_post('content');
		$this->published = ml_api_post('published');
		$this->category = ml_api_post('category');
		$this->photo = ml_api_post('photo');
		$this->audio = ml_api_post('audio');
		$this->video = ml_api_post('video');
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
	private function parse_json () {
		global $post;

		// Get all post properties from within properties
		$props = ml_api_post_json($post, 'properties', false);

		// Get all easy values here
		$this->name = ml_api_post_json($props, 'name', true);
		$this->summary = ml_api_post_json($props, 'summary', true);
		$this->published = ml_api_post_json($props, 'published', true);
		$this->category = ml_api_post_json($props, 'category', false);
		$this->photo = ml_api_post_json($props, 'photo', true);
		$this->audio = ml_api_post_json($props, 'audio', true);
		$this->video = ml_api_post_json($props, 'video', true);
		$this->mp_slug = ml_api_post_json($props, 'mp-slug', true);
		$this->bookmark_of = ml_api_post_json($props, 'bookmark-of', true);
		$this->in_reply_to = ml_api_post_json($props, 'in-reply-to', true);
		$this->like_of = ml_api_post_json($props, 'like-of', true);
		$this->repost_of = ml_api_post_json($props, 'repost-of', true);

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
