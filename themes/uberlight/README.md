# "Uberlight" Theme

This is an ultra lightweight theme that represents the bare minimum you need. It
is mainly used as a guide for making your own themes.

## 1. Global Variables

While global variables and functions aren't the nicest thing ever to use, they
make theme support so much easier. Here is a list of what you can access when
making your own themes:

* **`$showing`**: What page is being shown? The archive, a page or post? This
  value uses the enum `Show`, which is one of:
  * **`Show::ARCHIVE`**: The list of posts, usually the homepage.
  * **`Show::POST`**: A single post.
  * **`Show::ERROR404`**: For when a post you're trying to view is missing.
* **`$posts`**: Either a list of posts, or a single post, loaded from the
  database. This will either be an array of posts, with one or more posts if
  `$showing` is `Show::ARCHIVE`, zero if `$showing` is `Show::ERROR404`, or a
  single post (not an array) if `$showing` is `Show::POST`.
* **`User`**: All user details, like name, email, and identities.
  * **`User::NAME`**
  * **`User::EMAIL`**
  * **`User::IDENTITIES`**: A list of external profiles, each item contains:
    * `name`: The name of the site
    * `url`: A link to your profile on that site
  * **`User::NOTE`**: If you've got a description about yourself set, it's here.
* **`ml_*` functions**: There is a relatively large number of functions in the
  `functions.include.php` file that is worth taking a look at to help

## 2. `index.php`

Here is where all themes start. All you need to do is output some HTML, but
we've separated some of the logic into other files.

For simplicity's sake, no CSS is provided at all.

## 3. `meta.php`

This is where all the non-post metadata is processed, like the `<head>` tags and
pagination at the bottom of the page. It shows that using the right class names
is important for micropub and h-entry microformats to work correctly.

## 4. `entry.php`

All entry displaying logic is located here. The `entry` function inside it will
display an entire entry/post, with a couple of extra separated functions for
easier reading.

---

**Author:** Tom Gardiner (tom@tombofry.co.uk)
