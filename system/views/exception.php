<html lang='en'>
<head>
<style type='text/css'>
@charset "UTF-8";

html, body {
	font-family: 'Arial', 'Trebuchet MS', 'Verdana', sans-serif;
	font-size: 12pt;
	background: #fff;
}

.exception-dump {
	white-space: pre-wrap;
	font-family: fixed;
}
</style>
</head>
<body>
	<h1><?=h (get_class ($e)) ?></h1>
	<p class='exception-dump'><?=h ($e) ?></p>
</body>
</html>

