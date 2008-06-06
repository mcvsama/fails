<html lang='en'>
<head>
<style type='text/css'>
@charset "UTF-8";

html, body {
	font-family: 'Arial', 'Trebuchet MS', 'Verdana', sans-serif;
	font-size: 12pt;
	background: #fff;
}

h1, h2, h3, h4, h5, h6 {
	margin: 0.5em 0;
}

pre {
	white-space: pre-wrap;
	font-family: fixed;
	background: #f7f7f7;
	line-height: 130%;
	padding: 0 2px;
}
</style>
</head>
<body>
	<h1><?=h (get_class ($e)) ?></h1>
	<pre><?=h (capitalize ($e->getMessage())) ?></pre>
	<h2>Stack trace</h2>
	<pre><?=h ($e->getTraceAsString()) ?></pre>
	<? if (Fails::$request !== null): ?>
		<h2>Pre-routing parameters</h2>
		<h3>GET</h3>
		<pre><?=h (array_to_string (Fails::$request->g)) ?></pre>
		<h3>POST</h3>
		<pre><?=h (array_to_string (Fails::$request->p)) ?></pre>
	<? endif ?>
	<? if (Fails::$dispatcher->merged_params !== null): ?>
		<h2>Post-routing parameters</h2>
		<pre><?=h (array_to_string (Fails::$dispatcher->merged_params)) ?></pre>
	<? endif ?>
	<? if (Fails::$session !== null): ?>
		<h2>Session dump</h2>
		<pre><?=h (array_to_string (Fails::$session->get_all())) ?></pre>
	<? endif ?>
</body>
</html>

