<?php # vim: set fenc=utf8 ts=4 sw=4:

class PostgreSQLDriver extends DatabaseDriver
{
	private $pg_link;

	/**
	 * Ctor
	 *
	 * \param	hostname
	 * 			Hostname with optional port after ':'.
	 * \param	username
	 * 			Username.
	 * \param	password
	 * 			Password.
	 * \param	database
	 * 			Database name.
	 */
	public function __construct ($hostname, $username, $password, $database)
	{
		parent::__construct();

		$hostname = addslashes ($hostname);
		$username = addslashes ($username);
		$password = addslashes ($password);
		$database = addslashes ($database);

		$this->pg_link = @pg_connect ("host='{$hostname}' dbname='{$database}' user='{$username}' password='{$password}'");
		if (!$this->pg_link)
			throw new PostgreSQLConnectionException ($hostname, $username, $database);
	}

	/**
	 * Dtor
	 */
	public function __destruct()
	{
		if ($this->pg_link)
			pg_close ($this->pg_link);
	}

	/**
	 * Implementation of DatabaseDriver::exec().
	 */
	public function exec ($query)
	{
		$this->queries_count += 1;

		if ($query instanceof DatabaseQuery)
		{
			list ($sql, $parameters) = $query->create_bindings (new Bind ($this, 'transform_placeholders'));
			$r = @pg_query_params ($this->pg_link, $sql, $parameters);
			if ($r === false)
				throw new InvalidDatabaseQueryException ($query, pg_last_error ($this->pg_link));
			return new PostgreSQLResult ($this->pg_link, $r);
		}
		else
			throw new InvalidArguments ('PostgreSQLDriver::exec() argument must be of type DatabaseQuery');
	}

	/**
	 * Creates new placeholder names and parameters array
	 * to look like '$1 … $2 … $3' and ['val1', 'val2', 'val3'].
	 */
	public function transform_placeholders (array $bindings)
	{
		$out_placeholders = array();
		$out_parameters = array();
		$i = 1;
		foreach ($bindings as $placeholder => $value)
		{
			# PHP has broken pg_query_params, which cannot get boolean(false) argument.
			# Check if argument is of type bool, and if so — sqlize it.
			if (gettype ($value) == 'boolean')
				$value = $this->sqlize ($value);
			# Set new placeholder name and parameter value:
			$out_placeholders[$placeholder] = '$'.$i++;
			$out_parameters[] = $value;
		}
		return array ($out_placeholders, $out_parameters);
	}

	/**
	 * Creates new placeholder names and parameters array to single SQL string, w/o parameters array.
	 */
	public function transform_placeholders_for_bare_sql ($bindings)
	{
		$out_placeholders = array();
		$out_parameters = array();
		foreach ($bindings as $placeholder => $value)
			$out_placeholders[$placeholder] = $this->sqlize ($value);
		return array ($out_placeholders, $out_parameters);
	}

	/**
	 * Implementation of DatabaseDriver::escape_relation_name().
	 */
	public function escape_relation_name ($string)
	{
		return '"'.addslashes ($relation_name).'"';
	}

	/**
	 * Implementation of DatabaseDriver::dump_relations().
	 */
	public function dump_relations()
	{
		# TODO
	}

	/**
	 * Implementation of DatabaseDriver::dump_attributes_of().
	 */
	public function dump_attributes_of ($relation_name)
	{
		# TODO
	}

	##
	## Protected
	##

	/**
	 * Implementation of DatabaseDriver::escape().
	 */
	protected function escape ($string)
	{
		return pg_escape_string ($string);
	}

	##
	## Privates
	##

	private function escape_for_bytea ($data)
	{
		$r = '';
		$N = strlen ($data);
		for ($i = 0; $i < $N; ++$i)
			$r .= sprintf ('\\%03o', ord ($data[$i]));
		return $r;
	}

	private function unescape_for_bytea ($data)
	{
		return stripcslashes ($data);
	}
}

?>
