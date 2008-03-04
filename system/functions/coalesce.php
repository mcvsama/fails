<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Returns its first non-null argument.
 * If all arguments are null, returns null.
 */
function coalesce()
{
	$args = func_get_args();
	$a = null;
	while (count ($args))
		if (!is_null ($a = array_shift ($args)))
			return $a;
	return null;
}

?>
