<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class DatabaseResult implements ArrayAccess, Countable, Iterator
{
	private $size;
	private $position;
	private $affected_rows;

	# Relation's attribute names:
	private $attributes;

	# Array of arrays: query data:
	private $data;

	/**
	 * Ctor. Not available for user.
	 */
	protected function __construct ($size, $affected_rows, array $attributes, array $data)
	{
		$this->size = $size;
		$this->position = 0;
		$this->affected_rows = $affected_rows;
		$this->attributes = $attributes;
		$this->data = $data;
	}

	/**
	 * Returns array of attribute names.
	 */
	public function attributes()
	{
		return $this->attributes;
	}

	/**
	 * Returns number of tuples in result.
	 */
	public function size()
	{
		return $this->size;
	}

	/**
	 * Returns current cursor position.
	 * Will be between 0…size(). Position == size() means
	 * that result points after-the-end.
	 */
	public function position()
	{
		return $this->position;
	}

	/**
	 * Moves to next tuple in result.
	 * Returns true if movement is valid, otherwise: null.
	 * Same as Iterator::next().
	 */
	public function to_next()
	{
		return $this->next();
	}

	/**
	 * Returns true if size() == 0, false otherwise.
	 */
	public function is_empty()
	{
		return $this->size() == 0;
	}

	/**
	 * Clears result (frees memory).
	 */
	public function clear()
	{
	}

	/**
	 * Returns tuple at current position as map
	 * (attributes => values).
	 * Same as Iterator::current().
	 */
	public function values()
	{
		return $this->current();
	}

	/**
	 * Returns one attribute value.
	 */
	public function value ($attribute)
	{
		return $this->data[$this->position][$attribute];
	}

	/**
	 * Returns number of affected rows in last UPDATE/INSERT command.
	 */
	public function affected_rows()
	{
		return $this->affected_rows;
	}

	##
	## ArrayAccess
	##

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 */
	public function offsetExists ($offset)
	{
		return $offset >= 0 && $offset < $this->size;
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 */
	public function offsetGet ($offset)
	{
		return $this->data[$offset];
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 */
	public function offsetSet ($offset, $value)
	{
		throw new DatabaseResultReadOnlyException ($this);
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset().
	 */
	public function offsetUnset ($offset)
	{
		throw new DatabaseResultReadOnlyException ($this);
	}

	##
	## Iterator, Countable
	##

	public function current()
	{
		return $this->data[$this->position];
	}

	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		$this->position += 1;
		return $this->valid();
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function count()
	{
		return $this->size;
	}

	public function valid()
	{
		return $this->position < $this->size;
	}
}

?>
