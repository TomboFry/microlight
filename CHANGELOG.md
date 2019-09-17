# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.0.1 - 2019-09-17

### Added

* Dark mode for default theme (hint: there's an option in `index.php`)
* Media endpoint URL in HTML head section (in addition to micropub query)

### Fixed

* Uses current time if `published` value is an empty string on micropub API
* Properly escape values for meta tags in HTML head
* Prevent errors from occurring when trying to determine non-existent
  Content-Type
* Wrap long words and links in default theme
* Save user configuration propertly if note and links are empty

### Changed

* Resize profile to always be square

## v1.0.0 Initial Release - 2019-09-14

No changes were made. It was the first release, after all!
