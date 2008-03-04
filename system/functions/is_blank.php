<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Tests whether given string contains only whitespace.
 *
 * \param	string
 * 			String to test.
 */
function is_blank ($string)
{
	return preg_match ('/^\s*$/', $string);
}

?>
