# microlight (or µLight, or µlite, etc)

**Please note:** Not currently suitable for use in production.

## Why?

Microlight is a blogging engine based on [IndieWeb](https://indieweb.org)-based
concepts. This means support for:

* POSSE (posting on your site, automatically posting to other social media
  sites, and linking the two together)
	* This means comments and replies on those social media websites will
	  also appear on your site underneath the post
* Post formats:
	* Note ("tweets")
	* Article (blog posts)
	* Photo
	* Video
	* And more! (See [PostType](https://indieweb.org/Category:PostType))
* Replies
	* Other people with an IndieWeb compatible website will be able to
	  post comments on your blog using their own website as an account
* Owning your identity
	* Your website is a corner of the internet **you own** - no company
	  keeps a-hold of the information you post to it

## Requirements

* **PHP 7 or above**  
  While in theory microlight should work on PHP 5.6, this version is
  [no longer supported](https://secure.php.net/supported-versions.php) by the
  PHP group.
* **PDO SQLite**  
  I would like it to be database agnostic, but I haven't gotten that far yet.
* **Apache Rewrite Module (mod_rewrite)** (optional)  
  While this is not required, post permalinks are going to look *much* nicer,

## Installation

Will be updated as soon as more is implemented. Right now there's either nothing
here, or not enough to be considered publishable.
