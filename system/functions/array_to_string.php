<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Returns array dump as string.
 *
 * \param	array
 * 			array to dump.
 */
function array_to_string (array $array = null)
{
	if ($array === null)
		return 'null';
	$r = array();
	foreach ($array as $k => $v)
		if (is_array ($v))
			$r[] = var_export ($k, true)." => ".array_to_string ($v);
		else
			$r[] = var_export ($k, true)." => ".var_export ($v, true);
	return '{'.implode (', ', $r).'}';
}

?>
