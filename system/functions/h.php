<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Escapes string for (X)HTML/XML.
 *
 * \param	string
 * 			String to escape.
 */
function h ($string)
{
	if (is_null ($string))
		return null;
	return htmlspecialchars ($string, ENT_QUOTES, 'UTF-8');
}

?>
