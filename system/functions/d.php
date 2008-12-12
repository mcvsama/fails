<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Dumps object (var_dump) surrounded with <pre/>.
 *
 * \param	object
 * 			Object to dump.
 */
function d()
{
	$args = func_get_args();
	foreach ($args as $arg)
	{
		echo '<pre>';
		ob_start();
		var_dump ($arg);
		echo h (ob_get_clean());
		echo '</pre>';
	}
}

?>
