<?php

if (!defined('MICROLIGHT_INIT')) die();

function ml_get_not_blank($var) {
	return (isset($_GET[$var]) && $_GET[$var] !== "");
}


function ml_showing () {
	global $post_slug;
	global $post_tag;
	global $post_type;
	global $pagination;
	global $search_query;
	global $showing;

	if (ml_get_not_blank('post_slug')) {
		$post_slug = $_GET['post_slug'];
		$showing = Show::PAGE;
	} else {
		$showing = Show::ARCHIVE;
		$pagination = 0;


		if (ml_get_not_blank('post_tag')) {
			$post_tag = $_GET['post_tag'];
		}

		if (ml_get_not_blank('post_type')) {
			$post_type = $_GET['post_type'];
		}

		if (ml_get_not_blank('search_query')) {
			// Override other two fields if Search Query is present
			$post_tag = '';
			$post_type = '';

			$search_query = $_GET['search_query'];
		}

		if (ml_get_not_blank('page')) {
			$pagination = $_GET['page'] - 1;
			if ($pagination < 0) {
				throw new Exception("Page $pagination cannot be less than 0");
			}
		}
	}
}

function ml_database_setup () {
	global $db;
	global $Me;

	// Set up a connection to the database
	$db = new DB();

	// Load Identity
	$Me = (new Identity($db))->findOne();
	$Me->links = (new RelMe($db))->find();
}

function ml_load_posts () {
	global $db;
	global $post_slug;
	global $post_tag;
	global $post_type;
	global $search_query;
	global $pagination;
	global $showing;
	global $Posts;

	$where = [];
	$limit = Config::POSTS_PER_PAGE;
	$offset = Config::POSTS_PER_PAGE * $pagination;

	if ($post_slug !== '') {
		$limit = 1;
		$offset = 0;
		$where = [[
			'column' => 'slug',
			'operator' => SQLOP::EQUAL,
			'value' => $post_slug
		]];
	} else if ($post_tag !== '' || $post_type !== '') {
		if ($post_tag !== ''){
			array_push($where, [
				'column' => 'tags',
				'operator' => SQLOP::LIKE,
				'value' => "%$post_tag,%"
			]);
		}
		if ($post_type !== '') {
			array_push($where, [
				'column' => 'type',
				'operator' => SQLOP::EQUAL,
				'value' => $post_type
			]);
		}
	} else if ($search_query !== '') {
		$where = [[
			'column' => 'name',
			'operator' => SQLOP::LIKE,
			'value' => "%$search_query%"
		]];
	}

	// Run the SQL query
	$Posts = (new Post($db))->find($where, $limit, $offset);

	// If we're asking for a page or post, there should only ever be one
	// result, so process that here:
	if ($showing === Show::POST || $showing === Show::PAGE) {
		// If there is not 1 post, show a 404 error
		if (count($Posts) !== 1) {
			$showing = Show::ERROR404;
			$Posts = null;
		} else {
			// Otherwise, take the only post out of the array
			$Posts = $Posts[0];
		}
	}
}

// Close DB connection
function ml_database_close () {
	global $db;
	$db->close();
}
