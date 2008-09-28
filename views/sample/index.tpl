<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

{*
	This is a Smarty template!
	For documentation, visit http://www.smarty.net/
	
	All controller variables are automatically assigned.
*}

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$prototype|classify} &ndash; Helium Scaffold</title>
		<style type="text/css">
		{literal}
			body { background: white; font-family: 'Lucida Grande', 'Tahoma', sans-serif; text-align: left; margin: 5% 20%; font-size: 10pt; color: #333; }
			h1 { font-size: 48pt; margin: 0; letter-spacing: -.1em; color: #000; }
			kbd, code, pre { font-family: 'Lucida Sans Typewriter', 'Courier', monospace }
			a { color: #000; }
		{/literal}
		</style>
	</head>

	<body>
		<div id="wrap">
			<h1>{$prototype|classify} &rsaquo; browse</h1>
			<p>There are <strong>{$items|@count}</strong> {$prototype|@pluralize}.</p>
			<table>
				<thead>
					{foreach from=$fields item=f}<th>{$f}</th>{/foreach}
				</thead>
				<tbody>
				{foreach from=$items key=k item=user}
					<tr>
						{foreach from=$fields item=f}<td title="{$user->$f}">{$user->$f|truncate:20}</th>{/foreach}
					</tr>
				{/foreach}
				</tbody>
			</table>	
			{foreach from=$items key=k item=user}
			<p>Number #{$user->id} is <strong>{$user->display_name}</strong>. {if $user->openids}He has OpenIDs.{/if}</p>
			{/foreach}
		</div>
	</body>
</html>