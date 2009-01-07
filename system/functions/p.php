<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Prints object (print_r) surrounded with <pre/>.
 *
 * \param	object
 * 			Object to dump.
 */
function p()
{
	$args = func_get_args();
	foreach ($args as $arg)
	{
		echo '<pre>';
		ob_start();
		print_r ($arg);
		echo h (ob_get_clean());
		echo '</pre>';
	}
}

?>
