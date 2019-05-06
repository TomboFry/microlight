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
		$this->name = post('name');
		$this->summary = post('summary');
		$this->content = post('content');
		$this->published = post('published');
		$this->category = post('category');
		$this->photo = post('photo');
		$this->bookmark_of = post('bookmark-of');
		$this->in_reply_to = post('in-reply-to');
		$this->like_of = post('like-of');
		$this->repost_of = post('repost-of');
		$this->mp_slug = post('mp-slug');
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
