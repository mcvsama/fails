<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Capitalizes first letter of the string.
 *
 * \param	string
 * 			String to capitalize.
 */
function capitalize ($string)
{
	if (!$string)
		return '';
	$string[0] = strtoupper ($string[0]);
	return $string;
}

?>
