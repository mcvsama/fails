<?php # vim: set fenc=utf8 ts=4 sw=4:

class Cache implements ArrayAccess
{
	# Keys and values of this map will be added as a cache key for each entry:
	public $params = array();

	private $directory_name;

	/**
	 * Ctor
	 */
	public function __construct ($directory_name)
	{
		$this->directory_name = rtrim ($directory_name, '/');
	}

	/**
	 * Clears $params field.
	 */
	public function clear_params()
	{
		$this->params = array();
	}

	/**
	 * Returns time (Time) when object has been added to cache.
	 * Returns null if there is no such object in cache.
	 */
	public function timestamp ($offset)
	{
		if (!Fails::$config->fails->caching)
			return null;
		$t = filemtime ($this->prepare_offset ($offset));
		if ($t === null)
			return null;
		return Time::from_unix ($t);
	}

	##
	## ArrayAccess
	##

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 */
	public function offsetExists ($offset)
	{
		return Fails::$config->fails->caching && file_exists ($this->prepare_offset ($offset));
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 */
	public function offsetGet ($offset)
	{
		if (!Fails::$config->fails->caching)
			return null;
		$r = file_get_contents ($this->prepare_offset ($offset));
		if ($r === false)
			return null;
		return $r;
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 */
	public function offsetSet ($offset, $value)
	{
		if (Fails::$config->fails->caching)
			return file_put_contents ($this->prepare_offset ($offset), $value);
		return $value;
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
		return $this->directory_name.'/'.escape_filename ($offset).'{'.escape_filename ($this->stringify_params()).'}.cache';
	}

	/**
	 * Converts $keys map to string.
	 */
	private function stringify_params()
	{
		$a = array();
		foreach ($this->params as $k => $v)
			$a[] = urlencode ($k).'='.urlencode ($v);
		return join (';', $a);
	}
}

?>
