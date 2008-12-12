<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Convert options map to string ready to include into (x)HTML tag.
 */
function html_attributes_from (array $html_options)
{
	$s = '';
	foreach ($html_options as $name => $value)
		$s .= ' '.$name.'="'.h ($value).'"';
	return $s;
}

?>
