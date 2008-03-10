<?php # vim: set fenc=utf8 ts=4 sw=4:

class DatabaseQuery
{
	private $sql;
	private $parameters;

	/**
	 * Creates new Query.
	 *
	 * \param	sql
	 * 			SQL to use.
	 * \param	parameters
	 * 			Parameter bindings.
	 *
	 * \example	new DatabaseQuery ("SELECT * FROM users WHERE id = :1", array ($user->id));
	 */
	public function __construct ($sql, array $parameters = array())
	{
		$this->sql = $sql;
		$this->parameters = $parameters;
		$this->validate();
	}

	/**
	 * Creates new query.
	 *
	 * \param	sql
	 * 			SQL string.
	 * \param	…
	 * 			Additional parameters are _the_ parameter bindings.
	 *
	 * \example	DatabaseQuery::create ("SELECT * FROM users WHERE id = :1", $user->id);
	 */
	public static function create ($sql)
	{
		return new DatabaseQuery ($sql, array_slice (func_get_args(), 1));
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
	 * Quotes string to insert into SQL string that may contain
	 * characters that otherwise would be interpreted as eg. parameter bindings.
	 */
	public function q ($str)
	{
		# TODO
	}

	/**
	 * Validation helper.
	 */
	private function ...
}

?>
