<?php # vim: set fenc=utf8 ts=4 sw=4:

function ul_for (array $items, array $html_options = array())
{
}


function ol_for (array $items, array $html_options = array())
{
}


function dl_for (array $map, array $html_options = array())
{
}


function active_if ($boolean)
{
	if ($boolean)
		return "active";
}


function stylesheet_link_tag ($stylesheet, array $html_options = array())
{
	$rsrc = FAILS_ROOT.'/public/stylesheets/'.$stylesheet.'.css';
	$vsrc = Fails::$request->fully_qualified_base_url().'/stylesheets/'.$stylesheet.'.css?'.filemtime ($rsrc);
	$html_options = array_merge (array ('rel' => 'stylesheet', 'type' => 'text/css', 'href' => $vsrc), $html_options);
	return "<link".html_attributes_from ($html_options)."/>\n";
}


function javascript_include_tag ($javascript, array $html_options = array())
{
	$rsrc = FAILS_ROOT.'/public/javascripts/'.$javascript.'.js';
	$vsrc = Fails::$request->fully_qualified_base_url().'/javascripts/'.$javascript.'.js?'.filemtime ($rsrc);
	$html_options = array_merge (array ('type' => 'text/javascript', 'src' => $vsrc), $html_options);
	return "<script".html_attributes_from ($html_options)."></script>\n";
}


function stylesheets_bundle_tag ($name, array $stylesheets, array $html_options = array())
{
	# TODO assert that target dir is writable or throw error
	$newest = 0;
	$root = FAILS_ROOT.'/public/stylesheets/';
	$bundle_file = FAILS_ROOT.'/public/stylesheets/bundle.'.$name.'.css';
	foreach ($stylesheets as $stylesheet)
		if (($t = filemtime ($root.$stylesheet.'.css')) > $newest)
			$newest = $t;
	$t = filemtime ($bundle_file);
	if ($t === false || $newest > $t)
	{
		unlink ($bundle_file);
		foreach ($stylesheets as $stylesheet)
			file_put_contents ($bundle_file, file_get_contents ($root.$stylesheet.'.css')."\n", FILE_APPEND);
	}
	$vsrc = Fails::$request->fully_qualified_base_url().'/stylesheets/bundle.'.$name.'.css?'.filemtime ($bundle_file);
	$html_options = array_merge (array ('rel' => 'stylesheet', 'type' => 'text/css', 'href' => $vsrc), $html_options);
	return "<link".html_attributes_from ($html_options)."/>\n";
}


function javascripts_bundle_tag ($name, array $javascripts, array $html_options = array())
{
	# TODO assert that target dir is writable or throw error
	$newest = 0;
	$root = FAILS_ROOT.'/public/javascripts/';
	$bundle_file = FAILS_ROOT.'/public/javascripts/bundle.'.$name.'.js';
	foreach ($javascripts as $javascript)
		if (($t = filemtime ($root.$javascript.'.js')) > $newest)
			$newest = $t;
	$t = filemtime ($bundle_file);
	if ($t === false || $newest > $t)
	{
		unlink ($bundle_file);
		foreach ($javascripts as $javascript)
			file_put_contents ($bundle_file, file_get_contents ($root.$javascript.'.js')."\n", FILE_APPEND);
	}
	$vsrc = Fails::$request->fully_qualified_base_url().'/javascripts/bundle.'.$name.'.js?'.filemtime ($bundle_file);
	$html_options = array_merge (array ('type' => 'text/javascript', 'src' => $vsrc), $html_options);
	return "<script".html_attributes_from ($html_options)."></script>\n";
}

?>
