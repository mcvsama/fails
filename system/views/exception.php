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
	<? foreach (Fails::get_state() as $h => $c): ?>
		<h2><?=h ($h) ?></h2>
		<pre><?=h ($c) ?></pre>
	<? endforeach ?>
</body>
</html>

