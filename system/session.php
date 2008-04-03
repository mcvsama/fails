<?php # vim: set fenc=utf8 ts=4 sw=4:

class Session
{
	private $vars;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		session_start();
		session_name (CONFIG_SESSION_ID);
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
	 * Gets all values from session.
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
}

?>
