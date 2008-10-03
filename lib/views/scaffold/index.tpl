<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$prototype|classify} &ndash; Helium Scaffold</title>
		<style type="text/css">
		{literal}
			body { background: white; font-family: 'Lucida Grande', 'Tahoma', sans-serif; text-align: left; margin: 5% 20%; font-size: 10pt; color: #333; }
			h1 { font-size: 48pt; margin: 2em 0 .6em; letter-spacing: -.1em; color: #000 }
			h2 { font-size: 18pt; margin: 2em 0 .6em; letter-spacing: -.1em; color: #666; font-weight: normal; }
			h2 strong { font-size: 24pt; color: #000; }
			h2 span {
				background: #666;
				color: #eee;
				position: relative;	top: -.4em;
				font-size: .5em; letter-spacing: -.05em;
				margin: 0 .2em;
				padding: .2em .7em;
				-moz-border-radius: 1em
			}
			kbd, code, pre, table { font-family: 'Lucida Sans Typewriter', 'Courier', monospace }
			table { font-size: 8pt; border: solid #eee; border-spacing: 0; border-width: 1px 0 0 1px }
			td, th { padding: 4px 8px;border: solid #eee;  border-width: 0 1px 1px 0}
			p { line-height: 1.3; }
			a { color: #06f; }
			span {background:#eeeea8; padding: .1em .3em}
			tr:target {background:#eeeea8; font-weight:bold}
			p.version { font-size: 7pt; color: #666; padding-bottom: 5%; margin-top: 2em; text-align: center }
			th {border-bottom: 3px double #999 }
			.odd { background: #f9f9f9; }
		{/literal}
		</style>
	</head>

	<body>
		<div id="wrap">
			<h1><strong>{$prototype|classify}</strong> &rsaquo; browse</h1>
			<p>There {if $items|@count == 1}is only one {$prototype}.{else}are <strong>{$items|@count}</strong> {$prototype|pluralize}{/if}. You may also {link_to action=add}add{/link_to}.</p>
			<table>
				<thead>
				<th class="destroy">&#x2717;</th>
					{foreach from=$fields item=f}<th>{$f}</th>{/foreach}
				</thead>
				<tbody>
				{foreach from=$items key=k item=user}
					<tr id="{$prototype|underscore|lower}{$user->id}" class="{cycle values='odd,even'}">
						<td class="destroy">{link_to action=destroy id=$user->id}&#x2717;{/link_to}</td>
						{foreach from=$fields item=f}<td title="{$user->$f}">{$user->$f|truncate:20}</td>{/foreach}
					</tr>
				{/foreach}
				</tbody>
			</table>
			{foreach from=$has_many key=relate item=track}
			<h2><strong>{$prototype|classify}</strong> <span>has many</span> <strong>{$relate|classify|pluralize}</strong></h2>
			<table>
				<thead>
					{foreach from=$track.fields item=f}
					<th>{if $f == $track.foreign_id}<span>{/if}{$f}{if $f == $track.foreign_id}</span>{/if}</th>
					{/foreach}
				</thead>
				<tbody>
				{foreach from=$track.matches key=id item=thing}{if $thing}
					<tr>
						<td colspan="{$track.fields|@count}">({$thing|@count} match{if $thing|@count > 1}es{/if} for {$prototype|classify} <a href="#{$prototype|underscore|lower}{$id}">#{$id}</a>)</td>
					</tr>
					{foreach from=$thing key=k item=hmm}
						<tr class="{cycle values='odd,even'}">
							{foreach from=$track.fields item=f}<td title="{$hmm->$f}">{$hmm->$f|truncate:20}</th>{/foreach}
						</tr>
					{/foreach}
				{/if}{/foreach}
				</tbody>
			</table>
			{/foreach}
			<p class="version">Helium <strong><code>{$helium_version}</code></strong> in development mode.</p>
		</div>
	</body>
</html>