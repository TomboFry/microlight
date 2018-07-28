<?php

define('MICROLIGHT_INIT', true);

session_start();

require_once('includes/config.php');

function put_value ($val, $index = NULL) {
	echo ml_post_not_blank($val)
		? $index !== NULL
			? $_POST[$val][$index]
			: $_POST[$val]
		: '';
}

try {
	$db = new DB();
	$identity = new Identity($db);
	if ($identity->find_one() !== NULL) {
		header('Location: index.php');
	}
} catch (Exception $e) {}

if (isset($_POST['submit'])) {
	$errors = [];
	$services = [];

	try {
		if (!ml_post_not_blank('token')) array_push($errors, 'CSRF Token required');
		if (empty($_SESSION['token'])) array_push($errors, 'CSRF Token required');

		if (!hash_equals($_POST['token'], $_SESSION['token'])) array_push($errors, 'CSRF Token invalid');

		// Validate POST variables first
		if (!ml_post_not_blank('name')) array_push($errors, 'Name required');
		if (!ml_post_not_blank('email')) array_push($errors, 'Email required');
		foreach ($_POST['sm_service_names'] as $index => $name) {
			$url = $_POST['sm_service_urls'][$index];
			if (!empty($name) && empty($url)) {
				array_push($errors, "Service '$name' requires a URL");
			} else if (!empty($name) && !empty($url)) {
				array_push($services, [
					'name' => $name,
					'url' => $url
				]);
			}
		}

		if (count($errors) === 0) {
			$name = $_POST['name'];
			$email = $_POST['email'];
			$note = $_POST['note'];

			// Connect to DB
			$db = new DB();

			// Create identity table
			$identity = new Identity($db);
			$identity->create_table();

			// Create RelMe table
			$relme = new RelMe($db);
			$relme->create_table();

			// Create posts table
			$post = new Post($db);
			$post->create_table();

			$identity_id = $identity->insert([
				'name' => $name,
				'email' => $email,
				'note' => $note
			]);

			foreach ($services as $key => $value) {
				$relme->insert([
					'name' => $value['name'],
					'url' => $value['url'],
					'identity_id' => $identity_id
				]);
			}

			session_destroy();
		}
	} catch (Exception $e) {
		array_push($errors, $e->getMessage());
	}
} else {
	if (empty($_SESSION['token'])) {
		if (function_exists('random_bytes')){
			$_SESSION['token'] = bin2hex(random_bytes(32));
		} else if (function_exists('mcrypt_create_iv')) {
			$_SESSION['token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		} else if (function_exists('openssl_random_pseudo_bytes')) {
			$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
		} else {
			// Not recommended, but if none of the above functions
			// exist, well then...  ¯\_(ツ)_/¯
			$_SESSION['token'] = md5(uniqid(rand(), TRUE)) . md5(uniqid(rand(), TRUE));
		}
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
		.i-left { border-right: 0 !important }
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
			/* background-color: #4A3; */
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
			margin-top: 2.5px;
			border-bottom: 0.5px solid #184;
		}
	</style>
</head>
<body>
	<h1>Install Microlight</h1>
	<?php if (!empty($errors)) { ?>
	<div class='p w'>
		Some errors occurred during installation:
		<ul>
		<?php
			foreach($errors as $err) {
				echo "<li>$err</li>";
			}
		?>
		</ul>
	</div>
	<?php } else if (isset($_POST['submit'])){ ?>
	<p class='p s'>
		Installation successful! You can now create posts using a
		micropub editor/publisher.
	</p>
	<a class='f' href="<?php echo ml_base_url(); ?>">&lt; Go Home</a>
	</body></html>
	<?php die(); ?>
	<?php } else { ?>
	<p class='p w'>
		You are viewing this page because Microlight has not been
		completely set up. You will need to create an identity to begin
		using Microlight.
	</p>
	<?php } ?>
	<form action='' method='POST'>
		<div class='f'>
			<label for='name'>
				Name
				<span class='r'>required</span>
			</label>
			<input required type='text' name='name' id='name' value='<?php put_value('name'); ?>' />
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
			<input required type='email' name='email' id='email' value='<?php put_value('email'); ?>' />
			<span class='d p'>
				Your email is not sent to me, it is simply to
				display on your homepage as contact information.
				Also, if no social media profiles are provided
				below, you will still be able to log into this
				blog to administer it.
			</span>
		</div>
		<div class='f'>
			<label for='note'>Note / Tagline</label>
			<input type='text' name='note' id='note' />
			<span class='d p'>Describe yourself. What makes you, you?</span>
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
						value='<?php put_value('sm_service_urls', 2); ?>'
					/>
				</div>
			</div>
			<span class='d p b'>
				To authenticate using your website with one of
				these social media accounts (instead of email),
				<a href='<?php echo ml_base_url(); ?>'>your
				homepage</a> must appear on the accounts you
				have specified. See <a target='_blank' href='https://indieweb.org/IndieAuth'>IndieAuth</a>
				for more information.
			</span>
		</div>
		<input type='hidden' name='token' value='<?php echo $_SESSION['token']; ?>' />
		<div class='f b'>
			<input id='install' name='submit' type='submit' value='Install' />
		</div>
	</form>
</body>
</html>
<?php

die();
