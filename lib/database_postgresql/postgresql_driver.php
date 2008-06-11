<?php # vim: set fenc=utf8 ts=4 sw=4:

class PostgreSQLDriver extends DatabaseDriver
{
	private $pg_link;
	private $cached_dump_relations;

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
	 *
	 * \throws	PostgreSQLConnectionException
	 * 			when connection fails.
	 */
	public function __construct ($hostname, $username, $password, $database)
	{
		parent::__construct();

		$hostname = pg_escape_string ($hostname);
		$username = pg_escape_string ($username);
		$password = pg_escape_string ($password);
		$database = pg_escape_string ($database);

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
	 *
	 * \param	query
	 * 			DatabaseQuery object.
	 *
	 * \throws	InvalidDatabaseQueryException
	 * 			if query is invalid.
	 *
	 * \throws	InvalidArguments
	 * 			if parameter is not DatabaseQuery.
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
			return new PostgreSQLResult ($this, $this->pg_link, $r);
		}
		else
			throw new InvalidArguments ('PostgreSQLDriver::exec() argument must be of type DatabaseQuery');
	}

	/**
	 * Creates new placeholder names and parameters array
	 * to look like '$1 … $2 … $3' and ['val1', 'val2', 'val3'].
	 * For private use only, although it must be public!
	 */
	public function transform_placeholders (array $bindings)
	{
		$out_placeholders = array();
		$out_parameters = array();
		$i = 1;
		foreach ($bindings as $placeholder => $value)
		{
			if (gettype ($value) == 'array')
			{
				$p = array();
				foreach ($value as $w)
				{
					$p[] = '$'.$i++;
					$out_parameters[] = $w;
				}
				$out_placeholders[$placeholder] = implode (', ', $p);
			}
			else
			{
				# PHP has broken pg_query_params, which cannot get boolean(false) argument.
				# Check if argument is of type bool, and if so — sqlize it.
				if (gettype ($value) == 'boolean')
					$value = $this->sqlize ($value);
				# Set new placeholder name and parameter value:
				$out_placeholders[$placeholder] = '$'.$i++;
				$out_parameters[] = $value;
			}
		}
		return array ($out_placeholders, $out_parameters);
	}

	/**
	 * Creates new placeholder names and parameters array to single SQL string, w/o parameters array.
	 * This method is now unused.
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
		if ($this->cached_dump_relations !== null)
			return $this->cached_dump_relations;

		$sql = "SELECT * FROM information_schema.tables";
		$r = @pg_query ($this->pg_link, $sql);

		if ($r === false)
			throw new DatabaseException ("couldn't load list of relations: ".pg_last_error ($this->pg_link));

		$dump = array();
		while ($row = @pg_fetch_assoc ($r))
		{
			# Skip not-public:
			if ($row['table_schema'] != 'public')
				continue;
			$o = new stdclass();
			$o->name	= $row['table_name'];
			$o->type	= null;
			if ($row['table_type'] == 'BASE TABLE')		$o->type	= DatabaseDriver::TABLE;
			else if ($row['table_type'] == 'VIEW')		$o->type	= DatabaseDriver::VIEW;
			$dump[$o->name] = $o;
		}

		return $this->cached_dump_relations = $dump;
	}

	/**
	 * Implementation of DatabaseDriver::dump_attributes_of().
	 *
	 * \throws	DatabaseException
	 * 			when database query fails.
	 */
	public function dump_attributes_of ($relation_name)
	{
		# SQL taken from Ruby-on-Rails framework:
		$e_relation_name = pg_escape_string ($relation_name);
		$sql = "SELECT a.attname AS attribute, format_type(a.atttypid, a.atttypmod) AS type, d.adsrc AS default, a.attnotnull AS not_null
				FROM pg_attribute a LEFT JOIN pg_attrdef d
				ON a.attrelid = d.adrelid AND a.attnum = d.adnum
				WHERE a.attrelid = '$e_relation_name'::regclass
				AND a.attnum > 0 AND NOT a.attisdropped
				ORDER BY a.attnum;";
		$r = @pg_query ($this->pg_link, $sql);

		if ($r === false)
			throw new DatabaseException ("couldn't load attributes of relation '$relation_name': ".pg_last_error ($this->pg_link));

		$dump = array();
		while ($row = @pg_fetch_assoc ($r))
		{
			list ($type, $params) = $this->postgresql_type_details ($row['type']);
			$o = new stdclass();
			$o->name		= $row['attribute'];
			$o->type		= $type;
			$o->params		= $params;
			$o->allow_null	= $row['not_null'] == 'f';
			$dump[$o->name] = $o;
		}

		return $dump;
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

	/**
	 * Escapes bytea data. Method for internal use.
	 */
	public function escape_bytea ($data)
	{
		$r = '';
		$N = strlen ($data);
		for ($i = 0; $i < $N; ++$i)
			$r .= sprintf ('\\%03o', ord ($data[$i]));
		return $r;
	}

	/**
	 * Unescapes bytea data. Method for internal use.
	 */
	public function unescape_bytea ($data)
	{
		return stripcslashes ($data);
	}

	##
	## Privates
	##

	/**
	 * Returns Fails' standarized type name for given PostgreSQL type.
	 */
	private function postgresql_type_details ($pg_type)
	{
		$type = null;
		$params = new stdclass();

		# boolean
		if ($pg_type == 'boolean')
		{
			$type = 'boolean';
		}
		# bytea
		else if ($pg_type == 'bytea')
		{
			$type = 'binary';
			$params->min_size = 0;
			$params->max_size = 4294967296;
		}
		# character(size)
		else if (preg_match ('/^character\((.+)\)$/', $pg_type, $out))
		{
			$type = 'string';
			$params->min_length = (integer)$out[1];
			$params->max_length = $params->min_length;
		}
		# character varying | text
		else if ($pg_type == 'character varying' || $pg_type == 'text')
		{
			$type = 'string';
			$params->min_length = null;
			$params->max_length = null;
		}
		# character varying(max-size)
		else if (preg_match ('/^character varying\((.+)\)$/', $pg_type, $out))
		{
			$type = 'string';
			$params->min_length = 0;
			$params->max_length = (integer)$out[1];
		}
		# smallint # -32768 to +32767
		else if ($pg_type == 'smallint')
		{
			$type = 'integer';
			$params->min_value = -32768;
			$params->max_value = +32767;
		}
		# integer # -2147483648 to +2147483647
		else if ($pg_type == 'integer')
		{
			$type = 'integer';
			$params->min_value = -2147483648;
			$params->max_value = +2147483647;
		}
		# bigint # -9223372036854775808 to 9223372036854775807
		else if ($pg_type == 'bigint')
		{
			$type = 'bigint';
			$params->min_value = -9223372036854775808;
			$params->max_value = +9223372036854775807;
		}
		# date
		else if ($pg_type == 'date')
		{
			$type = 'date';
		}
		# time with time zone | time without time zone | time(precision) with timezone | time(precision) without time zone
		else if (preg_match ('/^time(\((.+)\))? with(out)? time zone$/', $pg_type, $out))
		{
			$type = 'time';
			$params->precision = isset ($out[2])? (integer)$out[2] : null;
			$params->with_timezone = @$out[3] != 'out';
		}
		# timestamp with time zone | timestamp without time zone | timestamp(precision) with time zone | timestamp(precision) without time zone
		else if (preg_match ('/^timestamp(\((.+)\))? with(out)? time zone$/', $pg_type, $out))
		{
			$type = 'timestamp';
			$params->precision = isset ($out[2])? (integer)$out[2] : null;
			$params->with_timezone = @$out[3] != 'out';
		}
		# interval | interval(precision)
		else if (preg_match ('/^interval(\((.+)\))?$/', $pg_type, $out))
		{
			$type = 'interval';
			$params->precision = isset ($out[2])? (integer)$out[2] : null;
		}
		# numeric | numeric(precision,scale)
		else if (preg_match ('/^numeric(\((.+),(.+)\))?$/', $pg_type, $out))
		{
			$type = 'numeric';
			$params->precision = isset ($out[2])? (integer)$out[2] : null;
			$params->scale = isset ($out[3])? (integer)$out[3] : null;
		}
		# real # 4 bytes, 6 decimal digits precision; 'Infinity', '-Infinity', 'NaN'
		else if ($pg_type == 'real')
		{
			$type = 'float';
			$params->precision = 6;
		}
		# double precision # 8 bytes, 15 decimal digits precision; 'Infinity', '-Infinity', 'NaN'
		else if ($pg_type == 'double precision')
		{
			$type = 'float';
			$params->precision = 15;
		}
		# bit(number)
		else if (preg_match ('/^bit\((.+)\)$/', $pg_type, $out))
		{
			$type = 'bitvector';
			$params->min_length = (integer)$out[1];
			$params->max_length = $params->min_length;
		}
		# bit varying
		else if ($pg_type == 'bit varying')
		{
			$type = 'bitvector';
			$params->min_length = null;
			$params->max_length = null;
		}
		# bit varying(max-number)
		else if (preg_match ('/^bit varying\((.+)\)$/', $pg_type, $out))
		{
			$type = 'bitvector';
			$params->min_length = 0;
			$params->max_length = (integer)$out[1];
		}

		return array ($type, $params);
	}
}

?>
