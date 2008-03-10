<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class DatabaseDriver
{
	# Transaction isolation levels:
	const SERIALIZABLE		= 'SERIALIZABLE';
	const REPEATABLE_READ	= 'REPEATABLE READ';
	const READ_COMMITED		= 'READ COMMITTED';
	const READ_UNCOMMITED	= 'READ UNCOMMITTED';

	# Transactions depth:
	private $transaction_depth;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->transaction_depth = 0;
	}

	/**
	 * Queries database in PostgreSQL-fashion. Arguments
	 * in @query_string are referenced by :1, :2 or by :{1}, :{2},
	 * or by :key1, :key2, :{key1}, :{key2}, ...
	 * Throws argument_exception when no arguments are given
	 * or when SQL statement is invalid or so.
	 * Returns object of type dbms_result as a result of query
	 * or null if there is no result.
	 *
	 * Examples:
	 *  - query (query_string, arg1, arg2, ...);
	 *  - query (query_string, array (arg1, arg2, ...));
	 *  - query (query_string, array (arg1name => arg1value, ...));
	 */
	abstract public function query ($sql_query);

	/**
	 * Executes query.
	 *
	 * \param	query
	 * 			Either DatabaseQuery object or SQL string.
	 * 			If engine supports parameter bindings in queries it may use
	 * 			it to execute query. Otherwise it may apply sqlize() method on
	 * 			each parameter to create SQL string and execute it.
	 */
	abstract public function exec ($query);

	/**
	 * Escapes string.
	 * Must be implemented in concrete driver.
	 *
	 * \returns	escaped string.
	 */
	abstract public function escape ($string);

	/**
	 * Unescapes string.
	 *
	 * \returns	unescaped string.
	 */
	abstract public function unescape ($string);

	/**
	 * Converts object to SQL string value:
	 *  * string is escaped and put between '',
	 *  * numbers are converted to string and returned,
	 *  * arrays are coverted to 'a, b, c' values, where each value is sqlized recursively.
	 *  * DatabaseQueries will be changed to SQL strings (useful for subqueries).
	 *
	 * \returns	SQL string.
	 */
	abstract public function sqlize ($object);

	/**
	 * Escapes relation name, that is puts it betwenn "" (PostgreSQL) or `` (MySQL).
	 *
	 * \returns	escaped string.
	 */
	abstract public function escape_relation_name ($string);

	/**
	 * Executes query.
	 *
	 * \returns	DatabaseResult object.
	 */
	abstract public function exec (DatabaseQuery $query);

	/**
	 * Returns relation info.
	 */
	abstract public function dump_relation_info ($relation_name);

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
}

?>
