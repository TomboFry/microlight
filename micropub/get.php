<?php

if (!defined('MICROLIGHT')) die();

// micropub?q=syndicate-to
// TODO: Once implemented, return details about syndication targets/encpoints.
// See:  https://www.w3.org/TR/micropub/#syndication-targets
function query_syndicate_to () {
	return syndicate_to();
}

// micropub?q=config
// TODO: Once implemented, return details about the media endpoint, and
//       syndication targets similarly to the above
function query_config () {
	// "new ArrayObject()" returns "{}" when JSON encoded
	// Source: https://stackoverflow.com/a/16665216
	return new ArrayObject();
}
