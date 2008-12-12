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


function stylesheet_link_tag ($stylesheet, array $html_options = array())
{
	$rsrc = FAILS_ROOT.'/public/stylesheets/'.$stylesheet.'.css';
	$vsrc = Fails::$request->fully_qualified_base_url().'/stylesheets/'.$stylesheet.'.css?'.filemtime ($rsrc);
	$html_options = array_merge (array ('rel' => 'stylesheet', 'type' => 'text/css', 'href' => $vsrc), $html_options);
	return "<link".html_attributes_from ($html_options)."/>";
}


function javascript_include_tag ($javascript, array $html_options = array())
{
	$rsrc = FAILS_ROOT.'/public/javascripts/'.$javascript.'.js';
	$vsrc = Fails::$request->fully_qualified_base_url().'/javascripts/'.$javascript.'.js?'.filemtime ($rsrc);
	$html_options = array_merge (array ('type' => 'text/javascript', 'src' => $vsrc), $html_options);
	return "<script".html_attributes_from ($html_options)."></script>";
}

?>
