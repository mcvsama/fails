<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class DatabaseDriver
{
	# Transaction isolation levels:
	const SERIALIZABLE		= 'SERIALIZABLE';
	const REPEATABLE_READ	= 'REPEATABLE READ';
	const READ_COMMITED		= 'READ COMMITTED';
	const READ_UNCOMMITED	= 'READ UNCOMMITTED';

	# Relation types:
	const TABLE	= 1;
	const VIEW	= 2;

	# Transactions depth:
	private $transaction_depth;

	# Queries counter:
	public $queries_count;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->transaction_depth = 0;
		$this->queries_count = 0;
	}

	/**
	 * Executes query.
	 *
	 * \param	query
	 * 			DatabaseQuery object.
	 * 			If engine supports parameter bindings in queries it may use
	 * 			it to execute query. Otherwise it may apply sqlize() method on
	 * 			each parameter to create SQL string and execute it.
	 *
	 * \returns	DatabaseResult object.
	 */
	abstract public function exec ($query);

	/**
	 * Converts object to SQL string value:
	 *  * string is escaped and put between '',
	 *  * numbers are converted to string and returned,
	 *  * arrays are coverted to 'a, b, c' values, where each value is sqlized recursively.
	 *  * DatabaseQueries will be changed to SQL strings (useful for subqueries).
	 *
	 * \throws	UnsupportedTypeForSQLException
	 * 			when object type cannot be mapped to SQL.
	 *
	 * \returns	SQL string.
	 */
	public function sqlize ($object)
	{
		switch (gettype ($object))
		{
			case 'boolean':
				return $object ? 'TRUE' : 'FALSE';
			case 'integer':
			case 'double':
				return "$object";
			case 'string':
				return "'".$this->escape ($object)."'";
			case 'array':
				$a = array();
				foreach ($object as $v)
					$a[] = $this->sqlize ($v);
				return implode (', ', $a);
			case 'NULL':
				return 'NULL';
			case 'object':
			case 'resource':
			case 'unknown type':
				throw new UnsupportedTypeForSQLException ($object);
		}
	}

	/**
	 * Escapes relation name, that is: puts it betwenn "" (PostgreSQL) or `` (MySQL).
	 *
	 * \returns	escaped string.
	 */
	abstract public function escape_relation_name ($string);

	/**
	 * Returns list of relations as array of objects with attributes:
	 *  name		=> 'relation name'
	 *  type		=> DatabaseDriver::TABLE | ::VIEW | null
	 */
	abstract public function dump_relations();

	/**
	 * Returns relation attributes info as array of objects with attributes:
	 *  name		=> 'attribute name',
	 *  type		=> 'attribute type',
	 *  params		=> map of type parameters,
	 *  allow_null	=> true/false.
	 * Attribute types are (params):
	 *  * 'boolean'
	 *  * 'binary'		(min_size, max_size)
	 *  * 'string'		(min_length, max_length)
	 *  * 'integer'		(min_value, max_value)
	 *  * 'numeric'		(precision, scale)
	 *  * 'float'		(precision)
	 *  * 'date'
	 *  * 'time'		(precision, with_timezone)
	 *  * 'datetime'	(precision, with_timezone)
	 *  * 'interval'	(precision)
	 *  * 'bitvector'	(min_length, max_length)
	 */
	abstract public function dump_attributes_of ($relation_name);

	/**
	 * Starts new transaction.
	 * May be overridden in concrete driver.
	 *
	 * \param	isolation_level
	 * 			Transaction isolation level. See constants.
	 */
	public function begin ($isolation_level = self::READ_COMMITED)
	{
		++$this->transaction_depth;
		if ($this->transaction_depth == 1)
		{
			$this->query ('BEGIN');
			$this->query ('SET TRANSACTION ISOLATION LEVEL '.$isolation_level);
		}
	}

	/**
	 * Commits transaction.
	 * Does nothing, if transaction wasn't started.
	 * May be overridden in concrete driver.
	 */
	public function commit()
	{
		--$this->transaction_depth;
		if ($this->transaction_depth == 0)
			$this->query ('COMMIT');
	}

	/**
	 * Cancels transaction.
	 * Does nothing, if transaction wasn't started.
	 * May be overridden in concrete driver.
	 */
	public function rollback()
	{
		--$this->transaction_depth;
		if ($this->transaction_depth == 0)
			$this->query ('ROLLBACK');
	}

	##
	## Protected
	##

	/**
	 * Escapes string.
	 * Must be implemented in concrete driver.
	 *
	 * \returns	escaped string.
	 */
	abstract protected function escape ($string);
}

?>
