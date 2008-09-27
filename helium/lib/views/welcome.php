<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Welcome to Helium!</title>
		<style type="text/css">
			body { background: white; font-family: 'Lucida Grande', 'Tahoma', sans-serif; text-align: center; }
			h1 { font-size: 48pt; margin-top: 20%; margin-bottom: 0; letter-spacing: -.1em }
			p.message { font-size: 12pt; color: #333; }
			p.version { font-size: 7pt; color: #666; }
			p.version code { color: #666; }
			kbd, code { font-weight: bold; color: #000; font-family: 'Lucida Sans Typewriter', 'Courier', monospace }
			a { color: #000; }
		</style>
	</head>

	<body>
		<div id="wrap">
			<?php if ($conf->production) { ?>
			<h1>Are you possibly <a href="http://en.wikipedia.org/wiki/John_Doe">John Doe</a>?</h1>
			<p>If you are, your name must be John. Go along, nothing to see here.</p>
			<?php } else { ?>
			<h1>Welcome to <a href="http://github.com/phrostypoison/helium">Helium</a>!</h1>
			<p class="message">If you are the developer of this website, please define a <kbd>home</kbd> controller to get rid of this message.</p>
			<p class="version">We call this version <code><?php echo HE_VERSION; ?></code>. Rock on.</p>
			<?php } ?>
		</div>
	</body>
</html>
