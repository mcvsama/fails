<?php # vim: set fenc=utf8 ts=4 sw=4:

class PostgreSQLResult extends DatabaseResult
{
	private $driver;
	private $pg_link;
	private $pg_result;

	public function __construct (PostgreSQLDriver $driver, $pg_link, $pg_result)
	{
		$this->driver = $driver;
		$this->pg_link = $pg_link;
		$this->pg_result = $pg_result;

		$attributes = array();
		$n = @pg_num_fields ($this->pg_result);
		for ($i = 0; $i < $n; ++$i)
			$attributes[] = @pg_field_name ($this->pg_result, $i);

		$data = array();
		while ($x = $this->fetch_next_tuplet())
			$data[] = $x;

		parent::__construct (@pg_num_rows ($this->pg_result), @pg_affected_rows ($this->pg_result), $attributes, $data);
	}

	/**
	 * Implementation of DatabaseResult::clear().
	 */
	public function clear()
	{
		if ($this->pg_result)
		{
			@pg_free_result ($this->pg_result);
			$this->data = null;
			$this->pg_result = null;
		}
	}

	##
	## Privates
	##

	/**
	 * Fetches next tuplet from result resource.
	 */
	private function fetch_next_tuplet()
	{
		$d = @pg_fetch_assoc ($this->pg_result);
		if (!$d)
			return;

		# Load result types:
		for ($i = 0; $i < count ($d); ++$i)
		{
			$n = @pg_field_name ($this->pg_result, $i);
			$v = $d[$n];
			if ($v !== null)
			{
				switch (@pg_field_type ($this->pg_result, $i))
				{
					case 'bool':
						$v = ($v == 't')? true : false;
						settype ($v, 'boolean');
						break;
					case 'int2':
					case 'int4':
					case 'int8':
						settype ($v, 'integer');
						break;
					case 'money':
					case 'numeric':
					case 'float4':
					case 'float8':
						settype ($v, 'float');
						break;
					case 'bytea':
						$v = $this->driver->unescape_bytea ($v);
						settype ($v, 'string');
						break;
					default:
						settype ($v, 'string');
				}
				$d[$n] = $v;
			}
		}

		return $d;
	}
}

?>
