<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Escapes string for CDATA section, that is
 * replaces every ]]> occurence with ]]>]]&gt;<![CDATA.
 *
 * \param	string
 * 			String to escape.
 */
function cdata_escape ($string)
{
	if (is_null ($string))
		return null;
	return str_replace (']]>', ']]>]]&gt;<![CDATA', $string);
}

?>
