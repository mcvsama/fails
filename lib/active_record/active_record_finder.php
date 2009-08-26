<?php # vim: set fenc=utf8 ts=4 sw=4:

class ActiveRecordFinder
{
	public function __construct()
	{
	}

	public function __destruct()
	{
	}

	/**
	 * Dynamic method catcher.
	 *
	 * find_by_x_and_y (x, y, options)		- returns one object
	 * find_all_by_x_and_y (x, y, options)	- returns array of objects
	 *
	 * count_by_x_and_y (x, y, options)		- counts all matching objects
	 *
	 * delete_by_x_and_y (x, y, options)	- deletes all matching objects
	 *
	 * destroy_by_x_and_y (x, y, options)	- destroys all matching objects
	 */
	public function __call ($name, $arguments)
	{
		# find(_all)_by_x_and_y_and_z (x, y, z)
		if (preg_match ('/^(count|find|delete|destroy)(_all)?_by_(.*)$/', $name, $out))
		{
			$do = $out[1];
			$all = $out[2] == '_all';
			$by = $out[3];

			$conditions = array();
			$options = array();

			$attributes = explode ('_and_', substr ($by, 1));
			if (count ($attributes) > count ($arguments))
				$options = $arguments[count ($attributes)];
			# Prepare conditions array:
			$i = 0;
			foreach ($attributes as $attribute)
				$conditions[] = "$attribute = :".++$i;
			$conditions = array (implode (' AND ', $conditions));
			$conditions = array_merge ($conditions, $arguments);

			if ($do == 'find')
				return $this->find_by ($conditions, $options);
			else if ($do == 'count')
				return $this->count_by ($conditions, $options);
			else if ($do == 'delete')
				return $this->delete_by ($conditions, $options);
			else if ($do == 'destroy')
				return $this->destroy_by ($conditions, $options);
		}
		else
			throw new MethodMissingException ($name, $this);
	}

	/**
	 * Finds object(s) by id or ids.
	 * If array is given, result is also an array.
	 */
	public function find ($id_or_ids)
	{
		# TODO
	}

	/**
	 * Returns all objects from the table.
	 */
	public function find_all()
	{
		# TODO
	}

	/**
	 * Finds and returns first object matching given conditions.
	 */
	public function find_by ($conditions, $options = array())
	{
		# TODO
	}

	public function find_all_by ($conditions, $options = array())
	{
		# TODO
	}

	public function count_by ($conditions, $options = array())
	{
		# TODO ->data('count') czy jakoś inaczej?
		$options['select'] = 'count(*)';
		return $this->query_by ($conditions, $options)->data ('count');
	}

	public function count_all()
	{
		# TODO
	}

	public function delete_by ($conditions, $options = array())
	{
		# TODO
	}

	public function delete_all()
	{
		# TODO
	}

	public function destroy_by ($conditions, $options = array())
	{
		# TODO
	}

	public function destroy_all()
	{
		# TODO
	}

	public function find_by_sql ($sql)
	{
		# TODO
	}

	public function find_all_by_sql ($sql)
	{
		# TODO
	}
}

