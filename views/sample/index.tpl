<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

{*
	This is a Smarty template!
	For documentation, visit http://www.smarty.net/

	All controller variables are automatically assigned.
*}

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Welcome to Helium!</title>
		<style type="text/css">
		{literal}
			body { background: white; font-family: 'Lucida Grande', 'Tahoma', sans-serif; text-align: center; }
			h1 { font-size: 48pt; margin-top: 20%; margin-bottom: 0; letter-spacing: -.1em }
			p.message { font-size: 12pt; color: #333; }
			p.version { font-size: 7pt; color: #666; }
			p.version code { color: #666; }
			kbd, code { font-weight: bold; color: #000; font-family: 'Lucida Sans Typewriter', 'Courier', monospace }
			a { color: #000; }
		{/literal}
		</style>
	</head>

	<body>
		<div id="wrap">
			<h1>Sample.</h1>
			<p>Please mess around with things {link_to controller='sample'}here{/link_to}.</p>
			<p class="version">You're on version <code>{$helium_version}</code>. Hastalavista, baby.</p>
		</div>
	</body>
</html>