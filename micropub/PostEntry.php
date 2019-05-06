<?php

if (!defined('MICROLIGHT')) die();

require_once('../includes/api.include.php');

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
		throw new Exception('Parsing JSON has not yet been implemented');
	}
}
