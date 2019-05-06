<?php

// This definition will prevent any files to be loaded outside of this file.
define('MICROLIGHT', 'v0.0.1');

session_start();
require_once('includes/config.php');
require_once('includes/lib/media.php');

function put_value ($val, $index = null) {
	echo ml_post_not_blank($val)
		? $index !== null
			? $_POST[$val][$index]
			: $_POST[$val]
		: '';
}

/**
 * Determines whether microlight has actually been installed.
 * Checks for user configuration and that the database can be queried.
 *
 * @return bool
 */
function is_installed () {
	if (!file_exists('includes/user.config.php')) {
		return false;
	}

	if (!file_exists(Config::DB_NAME . '.db')) {
		return false;
	}

	try {
		$db = new DB();
		$posts = new Post($db);
		if ($posts->find_one() === null) {
			return false;
		}
	} catch (Exception $e) {
		return false;
	}

	return true;
}

function quote ($string) {
	return preg_replace('/[\']/', '\\\'', $string);
}

/**
 * Creates the user configuration file
 *
 * @param string $name
 * @param string $email
 * @param string $note
 * @param array $identities
 * @return bool Successful or not
 */
function create_user_config ($name, $email, $note, $identities) {
	if (empty($name) || empty($email)) return false;

	// Escape quote characters
	$name = quote($name);
	$email = quote($email);

	// Create the contents of the `user.config.php` file
	$contents = '<?php
if (!defined(\'MICROLIGHT\')) die();

// You may edit these values at any time.
class User {
	const NAME = \'' . $name . '\';
	const EMAIL = \'' . $email .'\';
';

	// Add note, if provided
	if (!empty($note)) {
		$note = quote($note);
		$contents .= '	const NOTE = \'' . $note . '\';
';
	}

	// Open identities section, even if there are no identities provided
	$contents .= '	const IDENTITIES = [
';
	// Add identities, if provided
	if (is_array($identities) && count($identities) > 0) {
		foreach ($identities as $identity) {
			$id_name = quote($identity['name']);
			$id_url = quote($identity['url']);

			$contents .= '		[
			\'name\' => \'' . $id_name . '\',
			\'url\' => \'' . $id_url . '\',
		],
';
		}
	} else {
		$contents .= '		//	[ \'name\' => \'\', \'url\' => \'\' ],';
	}

	// Close identities section
	$contents .= '	];
';

	// Add the final closing curly bracket
	$contents .= '}';

	if (file_put_contents('includes/user.config.php', $contents) === false) {
		return false;
	}

	return true;
}

if (is_installed()) {
	unset($_SESSION['csrf_token']);
	header('Location: ' . ml_base_url());
	return;
}

if (isset($_POST['submit'])) {
	$errors = [];
	$services = [];

	try {
		// Ensure both tokens were provided
		if (!ml_post_not_blank('token')) array_push($errors, 'CSRF Token required');
		if (empty($_SESSION['csrf_token'])) array_push($errors, 'CSRF Token required');

		// Make sure they're equal
		if (!hash_equals($_POST['token'], $_SESSION['csrf_token'])) array_push($errors, 'CSRF Token invalid');

		// Validate POST variables first
		if (!ml_post_not_blank('name')) array_push($errors, 'Name required');
		if (!ml_post_not_blank('email')) array_push($errors, 'Email required');
		foreach ($_POST['sm_service_names'] as $index => $name) {
			$url = $_POST['sm_service_urls'][$index];
			if (!empty($name) && empty($url)) {
				array_push($errors, "Service '$name' requires a URL");
			} elseif (!empty($name) && !empty($url)) {
				array_push($services, [
					'name' => $name,
					'url' => $url,
				]);
			}
		}

		// Attempt to upload/resize profile picture
		if (isset($_FILES['photo'])) {
			$image = new ImageResizer($_FILES['photo'], 'me', 'image/jpg');
		}

		if (count($errors) === 0) {
			$name = $_POST['name'];
			$email = $_POST['email'];
			$note = $_POST['note'];

			// Connect to DB
			$db = new DB();

			// Create posts table
			$post = new Post($db);
			$post->create_table();

			if (create_user_config($name, $email, $note, $services) === false) {
				throw new Exception (
					'Could not create `user.config.php`. ' +
					'Check your file permissions, and that the file doesn\'t ' +
					'already exist.'
				);
			}

			session_destroy();
		}
	} catch (\Throwable $e) {
		array_push($errors, $e->getMessage());
	}
} else {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = ml_generate_token();
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Install Microlight</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			width: 100%;
			padding: 64px;
			max-width: 640px;
			margin: 0 auto;
			color: #333;
			line-height: 1;
		}

		body, input {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
		}

		h1 {
			font-weight: 300;
			font-size: 32pt;
		}

		body > * { margin-bottom: 16px }

		a { color: #3fa9f5 }

		a:hover { color: #3793d5 }

		.p { line-height: 1.4em }

		.i-left {
			border-right: 0 !important;
			width: 40% !important;
		}
		.i-right {
			width: 60% !important;
		}

		ul { margin-left: 24px }

		.f {
			display: block;
			width: 100%;
			padding: 24px 0;
		}

		.f label {
			display: block;
			font-weight: 500;
			margin-bottom: 8px;
		}

		.f input[type='text'], .f input[type='email'], .f input[type='url'] {
			display: block;
			width: 100%;
			padding: 8px;
			font-size: 24px;
			font-weight: 300;
			border: 1px solid #ccc;
			color: #555;
		}

		#l input[type='text'], #l input[type='email'], #l input[type='url'] {
			display: inline-block;
			width: 50%;
			margin-bottom: 8px;
			font-size: 18px;
		}

		.d {
			display: block;
			margin-top: 8px;
			font-size: 9pt;
			color: #999;
		}

		.b {
			margin-top: 0;
			padding-bottom: 0;
		}

		.r {
			color: #E34;
			font-weight: 700;
			margin-left: 8px;
			font-size: 10pt;
			float: right;
		}

		.w {
			padding: 16px;
			border: 1px solid #A12;
			background-color: #FDE;
			color: #E34;
			border-radius: 6px;
		}

		.s {
			padding: 16px;
			border: 1px solid #184;
			color: #184;
			border-radius: 6px;
		}

		#install {
			background: #4A3;
			background: linear-gradient(#5B5, #4A3);
			border: 0;
			border-bottom: 3px solid #184;
			padding: 16px;
			width: 100%;
			color: #F2F2F2;
			font-weight: 700;
			font-size: 16px;
			border-radius: 6px;
			transition: margin-top 0.1s, border 0.1s;
		}

		#install:active {
			margin-top: 2px;
			border-bottom: 1px solid #184;
		}
	</style>
</head>
<body>
	<h1>Install Microlight</h1>
	<?php if (!empty($errors)): ?>
	<div class='p w'>
		Some errors occurred during installation:
		<ul>
		<?php
			foreach ($errors as $err) {
				echo "<li>$err</li>";
			}
		?>
		</ul>
	</div>
	<?php elseif (isset($_POST['submit'])): ?>
	<p class='p s'>
		Installation successful! You can now create posts using a
		micropub editor/publisher.
	</p>
	<a class='f' href="<?php echo ml_base_url(); ?>">&lt; Go Home</a>
	</body></html>
	<?php die(); ?>
	<?php else: ?>
	<p class='p w'>
		You are viewing this page because Microlight has not been
		completely set up. You will need to create an identity to begin
		using Microlight.
	</p>
	<?php endif; ?>
	<form action='' method='POST' enctype="multipart/form-data">
		<div class='f'>
			<label for='name'>
				Name
				<span class='r'>required</span>
			</label>
			<input
				required
				type='text'
				name='name'
				id='name'
				value='<?php put_value('name'); ?>'
			/>
			<span class='d p'>
				Who do you identify as? This will be displayed
				prominently on your homepage and by every post
				you make.
			</span>
		</div>
		<div class='f'>
			<label for='email'>
				Email Address
				<span class='r'>required</span>
			</label>
			<input
				required
				type='email'
				name='email'
				id='email'
				value='<?php put_value('email'); ?>'
			/>
			<span class='d p'>
				Your email is not sent anywhere, it is simply to
				display on your homepage as contact information.
				Also, if no social media profiles are provided
				below, you will still be able to log into this
				blog to administer it.
			</span>
		</div>
		<div class='f'>
			<label for='name'>
				Photo
			</label>
			<input
				required
				type='file'
				name='photo'
				id='photo'
				accept='image/*'
			/>
			<span class='d p'>
				What do you look like? This will act as your
				"profile picture", and be used when interacting
				with other websites. Optional, but highly
				recommended.
			</span>
		</div>
		<div class='f'>
			<label for='note'>Note / Tagline</label>
			<input type='text' name='note' id='note' />
			<span class='d p'>
				Describe yourself. What makes you, you?
			</span>
		</div>
		<div class='f'>
			<label for='l'>
				Social Media Accounts
			</label>
			<div id='l'>
				<div class='account'>
					<input
						type='text'
						placeholder='Name (eg. "Twitter")'
						name='sm_service_names[]'
						class='i-left'
						value='<?php put_value('sm_service_names', 0); ?>'
					/><input
						type='url'
						placeholder='URL'
						name='sm_service_urls[]'
						class='i-right'
						value='<?php put_value('sm_service_urls', 0); ?>'
					/>
				</div>
				<div class='account'>
					<input
						type='text'
						placeholder='Name (eg. "GitHub")'
						name='sm_service_names[]'
						class='i-left'
						value='<?php put_value('sm_service_names', 1); ?>'
					/><input
						type='url'
						placeholder='URL'
						name='sm_service_urls[]'
						class='i-right'
						value='<?php put_value('sm_service_urls', 1); ?>'
					/>
				</div>
				<div class='account'>
					<input
						type='text'
						placeholder='Name'
						name='sm_service_names[]'
						class='i-left'
						value='<?php put_value('sm_service_names', 2); ?>'
					/><input
						type='url'
						placeholder='URL'
						name='sm_service_urls[]'
						class='i-right'
						value='<?php put_value('sm_service_urls', 2); ?>'
					/>
				</div>
			</div>
			<span class='d p b'>
				To authenticate using your website with one of
				these social media accounts (instead of email),
				<a href='<?php echo ml_base_url(); ?>'>your
				homepage</a> must appear on the accounts you
				have specified. See
				<a target='_blank' href='https://indieweb.org/IndieAuth'>IndieAuth</a>
				for more information.
			</span>
		</div>
		<input
			type='hidden'
			name='token'
			value='<?php echo $_SESSION['csrf_token']; ?>'
		/>
		<div class='f b'>
			<input
				id='install'
				name='submit'
				type='submit'
				value='Install'
			/>
		</div>
	</form>
</body>
</html>
<?php

die();
