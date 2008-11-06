<?php # vim: set fenc=utf8 ts=4 sw=4:

/**
 * Encodes data to JSON and escapes with cdata_escape.
 * Return string is ready to be inserted directly into Javascript's CDATA section.
 *
 * \param	object
 * 			Object to encode and escape.
 */
function j ($object)
{
	return cdata_escape (json_encode ($object));
}

?>
