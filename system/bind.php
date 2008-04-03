<?php # vim: set fenc=utf8 ts=4 sw=4:

class Bind
{
	private $a;
	private $b;

	/**
	 * TODO
	 */
	public function __construct ($a, $b = null)
	{
		$this->a = $a;
		$this->b = $b;
	}

	/**
	 * TODO
	 */
	public function call()
	{
		$args = func_get_args();
		if ($this->b === null)
		{
			if (is_string ($this->a))
				return call_user_func_array ($this->a, $args);
			else
				throw new Exception(); # TODO InvalidBindException
		}
		else
		{
			return call_user_func_array (array ($this->a, $this->b), $args);
		}
	}
}

?>
