<?php # vim: set fenc=utf8 ts=4 sw=4:

class DatabaseQuery
{
	private $sql;
	private $parameters;

	/**
	 * Creates new Query.
	 *
	 * \param	sql
	 * 			SQL command.
	 * \param	…
	 * 			Additional parameters are _the_ parameter bindings.
	 *
	 * \example	new DatabaseQuery ("SELECT * FROM users WHERE id = :1", $user->id);
	 */
	public function __construct ($sql)
	{
		$this->sql = $sql;
		$this->parameters = array_slice (func_get_args(), 1);
		$this->validate();
	}

	/**
	 * Checks if all placeholders in SQL have corresponding
	 * parameters.
	 *
	 * \throws	MissingParametersForPlaceholdersException
	 * 			When not all placeholders have their corresponding parameters.
	 */
	public function validate()
	{
		# TODO
		# query => array ('param-name' => 'value') => database-driver => array ('param-name' => 'escaped-value' or '$1') => query
		# Query returns array of parameters
	}

	/**
	 * Escapes string to insert into SQL string that may contain
	 * characters that otherwise would be interpreted as eg. parameter bindings.
	 */
	public static function e ($string)
	{
		return str_replace (':', '::', $string);
	}

	/**
	 * \returns	array containing 'sql' and 'parameters' in such order.
	 * 			'sql' is the SQL to use, 'parameters' is an array of parameter values.
	 */
	public function create_bindings (Bind $callback)
	{
		list ($placeholders, $parameters) = $callback->call ($this->parameters);
		$sql = preg_replace ('/(::)|:{(\w+)}|:(\w+)/e', '$this->bindings_replacer ($placeholders, \'$1\', \'$2\', \'$3\')', $this->sql);
		return array ($sql, $parameters);
	}

	/**
	 * Helper for create_bindings.
	 */
	private function bindings_replacer (&$placeholders, $colons, $a, $b)
	{
		if ($colons === '::')
			return ':';
		return $placeholders[($a? $a : $b) - 1];
	}
}


/**
 * DatabaseQueryWithArray
 *
 * Variation of DatabaseQuery with explicit bindings parameter
 * in constructor.
 */
class DatabaseQueryWithArray extends DatabaseQuery
{
	/**
	 * Creates new Query
	 *
	 * \param	sql
	 * 			SQL command.
	 * \param	parameters
	 * 			Map/array of parameter bindings.
	 *
	 * \example	new DatabaseQueryWithArray ("SELECT * FROM users WHERE id = :1", array ($user->id));
	 */
	public function __construct ($sql, $parameters = array())
	{
		call_user_func_array (array ('parent', '__construct'), array_merge (array ($sql), $parameters));
	}
}

?>
