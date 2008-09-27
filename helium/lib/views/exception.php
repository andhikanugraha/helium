<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Error</title>
		<style type="text/css">
			body { position: fixed; }
			#exception { position:fixed; overflow: auto; top: 0; left: 0; width: 100%; height: 100%; background: white; font-family: 'Lucida Grande', 'Tahoma', sans-serif; text-align: center; }
			h1 { font-size: 36pt; margin-top: 20%; margin-bottom: 0; letter-spacing: -.1em }
			p.message { font-size: 12pt; color: #333; }
		<?php if (!$conf->production) { ?>
			h1 { margin-top: 5%; }
			p.debug { width: 18em; margin: 1em auto; padding-top: 1em; text-align: left; color: #666; font-size: 9pt; overflow: visible }
			h2 { font-weight: normal; font-size: 16pt }
			kbd, code { font-family: 'Lucida Sans Typewriter', 'Courier', monospace }
			kbd.variable { padding: 2px 5px; background: #eeeeb8 }
			code.string { color: #d00 }
			code.value { color: #00b }
			.trace { padding-bottom: 20px; }
			.trace table, .params table { width: 60%; margin: 0 auto; text-align: left; font-size: 9pt; border-left: 1px dotted #eee; border-right: 1px dotted #eee; padding: 0 1px; border-spacing: 0 }
			.params table { width: 40%; }
			.trace td, .trace th, .params td, .params th { vertical-align: top; padding: 3px 10px }
			.trace tr.odd-trace td, .params tr.odd-param td { background: #f6f6f6; }
			.trace td.ordinal, .params td.ordinal { color: #666; }
			span.null, code.null { color: #999; }
			table.request { width: 360px; margin: 0 auto; font-size: 8pt }
			table.request th { font-size: 7.5pt; padding: 0 10px; width: 120px; font-weight: normal; color: #666 }
			table.request td { font-family: 'Lucida Sans Typewriter', 'Courier', monospace; padding: 0 10px; width: 120px; overflow: auto }
			p.fault { font-size: 9pt; color: #666 }
		<?php } ?>
		</style>
	</head>

	<body>
		<div id="exception">
			<?php if ($conf->production) { ?>
			<h1>Oops! Something bad happened.</h1>
			<p>Please check back in a few minutes.</p>
			<?php } else { ?>
			<h1>Exception caught</h1>
			<p class="message"><?php echo $controller->message; ?></p>
			<p class="fault"><?php echo $controller->formatted_filename; ?> line <?php echo $controller->line; ?></p>
			<table class="request">
				<tr>
					<th>Request</th>
					<th>Controller</th>
					<th>Action</th>
				</tr>
				<tr>
					<td><?php echo $controller->request; ?></td>
					<td><?php echo $controller->controller; ?></td>
					<td><?php echo $controller->action; ?></td>
				</tr>
			</table>
			<div class="params">
				<h2>Script parameters</h2>
				<table>
					<tr>
						<th style="width: 30%">Parameter</th>
						<th style="width: 70%">Value</th>
					</tr>
				<?php foreach ($controller->params as $k => $v) { $cycle = true; ?>
					<tr class="<?php if ($cycle) echo 'odd-param'; else echo 'even-param'; ?>">
						<td><?php echo $k ?></td>
						<td><?php echo $v; if ($v === null) { ?><code class="null">null</code><?php } ?></td>
					</tr>
				<?php } ?>
				</table>
			</div>
			<div class="trace">
				<h2>Execution trace</h2>
				<table>
					<tr>
						<th width="1%"></th>
						<th style="width: 28%">File</th>
						<th style="width: 1%">Line</th>
						<th style="width: 25%">Function</th>
						<th style="width: 35%">Arguments</th>
					<tr>
				<?php foreach ($controller->formatted_trace as $num => $line) { $cycle = true; ?>
					<tr class="<?php if ($cycle) echo 'odd-trace'; else echo 'even-trace'; ?>">
						<td class="ordinal"><?php echo $num; ?></td>
						<td><?php echo $line['file']; ?></td>
						<td><?php echo $line['line']; ?></td>
						<td class="function"><?php echo $line['class'] . $line['type'] . $line['function']; ?></td>
						<td class="arguments"><?php echo $line['args']; if ($line['args'] === null) { ?><code class="null">null</code><?php } ?></td>
					</tr>
				<?php } ?>
				</table>
			</div>
			<?php } ?>
		</div>
	</body>
</html>