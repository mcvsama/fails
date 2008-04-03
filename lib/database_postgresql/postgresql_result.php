<?php # vim: set fenc=utf8 ts=4 sw=4:

class PostgreSQLResult extends DatabaseResult
{
	private $pg_link;
	private $pg_result;

	public function __construct ($pg_link, $pg_result)
	{
		$this->pg_link = $pg_link;
		$this->pg_result = $pg_result;

		# TODO
	}

	/**
	 * Implementation of DatabaseResult::attributes().
	 */
	public function attributes()
	{
	}

	/**
	 * Implementation of DatabaseResult::size().
	 */
	public function size()
	{
	}

	/**
	 * Implementation of DatabaseResult::position().
	 */
	public function position()
	{	
	}

	/**
	 * Implementation of DatabaseResult::to_next().
	 */
	public function to_next()
	{
	}

	/**
	 * Implementation of DatabaseResult::to_prev().
	 */
	public function to_prev()
	{
	}

	/**
	 * Implementation of DatabaseResult::is_empty().
	 */
	public function is_empty()
	{
	}

	/**
	 * Implementation of DatabaseResult::clear().
	 */
	public function clear()
	{
	}

	/**
	 * Implementation of DatabaseResult::values().
	 */
	public function values()
	{
	}
}

?>
