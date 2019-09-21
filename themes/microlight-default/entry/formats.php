<?php

// Stop the rest of the page from processing if we're not actually loading this
// file from within microlight itself.
if (!defined('MICROLIGHT')) die();

function fmt_default ($post, $is_archive) {
	entry_title($post, $is_archive);
	entry_content($post, $is_archive);
}

function fmt_image ($post, $is_archive) {
	entry_title($post, $is_archive);
	echo "<img ";
	echo "class='u-photo' ";
	echo "alt='" . $post['name'] . "' ";
	echo "src='" . $post['url'] . "' />";
	entry_content($post, $is_archive);
}

function fmt_audio ($post, $is_archive) {
	entry_title($post, $is_archive);
	echo "<audio class='u-audio' controls>";
		echo "<source src='" . $post['url'] . "' />";
		echo "<p>Your browser does not support this audio format</p>";
	echo "</audio>";
	entry_content($post, $is_archive);
}

function fmt_video ($post, $is_archive) {
	entry_title($post, $is_archive);
	echo "<video class='u-video' controls>";
		echo "<source src='" . $post['url'] . "' />";
		echo "<p>Your browser does not support this video format</p>";
	echo "</video>";
	entry_content($post, $is_archive);
}

function fmt_scrobble ($post, $is_archive) {
	$url = $post['url'];
	if (empty($url)) {
		$url = ml_post_permalink($post['slug']);
	}
	echo "<h2 class='p-name'>";
		echo "<span class='p-name-emoji'>&#127911;</span>";
		echo "<a class='u-listen-of' href='" . $url . "'>";
			echo $post['name'];
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_bookmark ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "<span class='p-name-emoji'>&#128278;</span>";
		echo "<a class='u-bookmark-of h-cite' href='" . $post['url'] . "'>";
		if (empty($post['name'])) {
			echo $post['url'];
		} else {
			echo $post['name'];
		}
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_like ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "<span class='p-name-emoji'>&#10084;&#65039;</span>";
		echo "<a class='u-like-of' href='" . $post['url'] . "'>";
		if (empty($post['name'])) {
			echo $post['url'];
		} else {
			echo $post['name'];
		}
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_repost ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "<span class='p-name-emoji'>&#128260;</span>";
		echo "<a class='u-repost-of' href='" . $post['url'] . "'>";
		if (empty($post['name'])) {
			echo $post['url'];
		} else {
			echo $post['name'];
		}
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}

function fmt_reply ($post, $is_archive) {
	echo "<h2 class='p-name'>";
		echo "<span class='p-name-emoji'>&#128172;</span>";
		echo "<a class='u-in-reply-to' href='" . $post['url'] . "'>";
		if (empty($post['name'])) {
			echo $post['url'];
		} else {
			echo $post['name'];
		}
		echo "</a>";
	echo "</h2>";
	entry_content($post, $is_archive);
}
