<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';

class ActiveRecordFinder
{
	/**
	 * Takes variable number of arguments either record IDs or one array containing record IDs.
	 * If array is given function will return array (even if array contains one element).
	 * If IDs are passed directly as arguments, array is returned only when there are 2 or more
	 * arguments.
	 *
	 * Examples:
	 *   find (3) -> Returns record with ID = 3
	 *   find (array (3)) -> Returns array containing one record with ID = 3
	 *   find (3, 4) or find (array (3, 4)) -> Returns array of records with IDs 3 and 4
	 */
	public function find()
	{
		# eeee TODO
		$ids = func_get_args();
		if (is_array ($ids[0]))
			$ids = $ids[0];
		if (is_array ($ids[0]) && count ($ids) > 0)
			throw new ArgumentException ("");
	}
}

?>
