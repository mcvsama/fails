<?php # vim: set fenc=utf8 ts=4 sw=4:

interface Marshal
{
	/**
	 * \returns	string
	 */
	public static function dump ($object);

	/**
	 * \returns	object
	 */
	public static function restore ($string);
}

?>
