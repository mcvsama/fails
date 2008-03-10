<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class DatabaseResult
{
	/**
	 * Returns array of attribute names.
	 */
	abstract public function attributes();

	/**
	 * Returns number of tuples in result.
	 */
	abstract public function size();

	/**
	 * Returns current cursor position.
	 * Will be between 0…size()-1.
	 */
	abstract public function position();

	/**
	 * Moves to next tuple in result.
	 * Returns true if movement is valid, otherwise: null.
	 */
	abstract public function to_next();

	/**
	 * Moves to previous tuple in result.
	 * Returns true if movement is valid, otherwise: null.
	 */
	abstract public function to_prev();

	/**
	 * Returns true if size() == 0, false otherwise.
	 */
	abstract public function is_empty();

	/**
	 * Clears result (frees memory).
	 */
	abstract public function clear();

	/**
	 * Returns tuple at current position as map
	 * (attributes => values).
	 */
	abstract public function values();
}

?>
