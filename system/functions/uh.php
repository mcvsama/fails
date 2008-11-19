<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Complementary function to h().
 * Decodes entities to characters.
 *
 * \param	string
 * 			String to unescape.
 */
function uh ($string)
{
	if (is_null ($string))
		return null;
	return html_entity_decode ($string, ENT_QUOTES, 'UTF-8');
}

?>
