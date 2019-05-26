<?php

if (!defined('MICROLIGHT')) die();

function post_delete_post ($slug) {
	$db = new DB();
	$post = new Post($db);

	$where = [
		SQL::where_create('slug', $slug, SQLOP::EQUAL, SQLEscape::SLUG),
		SQL::where_create('status', 'deleted', SQLOP::NEQUAL, SQLEscape::POST_TYPE),
	];

	// Check if the post exists before trying to delete it
	if ($post->count($where) === 0) throw new Exception('Post does not exist');

	$properties = [
		'status' => 'deleted',
	];

	return $post->update($properties, $where);
}
