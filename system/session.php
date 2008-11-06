<?php # vim: set fenc=utf8 ts=4 sw=4:

class Session implements ArrayAccess
{
	private $vars;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$sn = Fails::$config->fails->session->id;
		if (!is_string ($sn) || is_blank ($sn))
			throw new SecurityException ('session identifier not set: $config->fails->session->id');
		@session_set_cookie_params (
			coalesce (Fails::$config->fails->session->timeout, 3600),
			coalesce (Fails::$config->fails->session->path, '/'),
			coalesce (Fails::$config->fails->session->domain),
			coalesce (Fails::$config->fails->session->secure, false)
		);
		session_name ($sn);
		session_start();
		$this->vars = &$_SESSION;
		unset ($_SESSION);
	}

	/**
	 * Sets value in session.
	 */
	public function set ($key, $value)
	{
		$this->vars[$key] = $value;
	}

	/**
	 * Gets value from session.
	 */
	public function get ($key)
	{
		return $this->vars[$key];
	}

	/**
	 * Gets all values from session in cloned map.
	 */
	public function get_all()
	{
		$clone = array();
		foreach ($this->vars as $key => $value)
			$clone[$key] = $value;
		return $clone;
	}

	/**
	 * Removes value from session and returns it.
	 */
	public function delete ($key)
	{
		$v = $this->vars[$key];
		unset ($this->vars[$key]);
		return $v;
	}

	/**
	 * Destroys all data in session.
	 */
	public function destroy()
	{
		foreach ($this->vars as $key => $value)
			$this->delete ($key);
	}

	##
	## ArrayAccess
	##

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 */
	public function offsetExists ($offset)
	{
		return $this->get ($offset) !== null;
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 */
	public function offsetGet ($offset)
	{
		return $this->get ($offset);
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 */
	public function offsetSet ($offset, $value)
	{
		return $this->set ($offset, $value);
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset().
	 */
	public function offsetUnset ($offset)
	{
		return $this->delete ($offset);
	}
}

?>
