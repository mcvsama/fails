<?php # vim: set fenc=utf8 ts=4 sw=4:

class Inflector
{
	/**
	 * Changes argument written in_underscore_notation
	 * to CamelCaseNotation.
	 *
	 * \param	string
	 * 			String to convert.
	 * \param	start_with_lower
	 * 			Whether first character in CamelCase should be lower case.
	 */
	public static function camelize ($string, $start_with_lower = false)
	{
		$string = str_replace (' ', '', ucwords (str_replace ('_', ' ', $string)));
		if ($start_with_lower)
			$string[0] = strtolower ($string[0]);
		return $string;
	}

	/**
	 * Changes argument written CamelCaseNotation
	 * to underscore_notation.
	 *
	 * \param	string
	 * 			String to convert.
	 */
	public static function underscore ($string)
	{
		return strtolower (preg_replace ('/(?<=\\w)([A-Z])/', '_\\1', $string));
	}

	/**
	 * Creates human-readable string from underscored-string.
	 *
	 * \param	string
	 * 			Underscored-string to convert.
	 */
	public static function humanize ($string)
	{
		return ucwords (str_replace ('_', ' ', $string));
	}

	/**
	 * Converts underscored controller name (as in routes)
	 * to controller class name.
	 */
	public static function to_controller_name ($string)
	{
		return Inflector::camelize ($string).'Controller';
	}

	/**
	 * Converts action name (as in routes)
	 * to method name.
	 */
	public static function to_action_name ($string)
	{
		return '_'.$string;
	}
}

?>
