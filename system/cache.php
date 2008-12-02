<?php # vim: set fenc=utf8 ts=4 sw=4:

class Cache implements ArrayAccess
{
	private $directory_name;

	/**
	 * Ctor
	 */
	public function __construct ($directory_name)
	{
		$this->directory_name = rtrim ($directory_name, '/');
	}

	##
	## ArrayAccess
	##

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 */
	public function offsetExists ($offset)
	{
		return file_exists ($this->prepare_offset ($offset));
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 */
	public function offsetGet ($offset)
	{
		return file_get_contents ($this->prepare_offset ($offset));
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 */
	public function offsetSet ($offset, $value)
	{
		return file_put_contents ($this->prepare_offset ($offset), $value);
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset().
	 */
	public function offsetUnset ($offset)
	{
		return unlink ($this->prepare_offset ($offset));
	}

	/**
	 * Escapes offset value.
	 */
	private function prepare_offset ($offset)
	{
		return $this->directory_name.'/'.escape_filename ($offset).'.cache';
	}
}

?>
