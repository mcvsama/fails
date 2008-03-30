<?php # vim: set fenc=utf8 ts=4 sw=4:

class Flash extends Library
{
	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->session = Fails::$session;
	}

	/**
	 * Adds 'warning' message.
	 */
	public function warning ($message)
	{
		$this->add ('warning', $message);
	}

	/**
	 * Adds 'message' message.
	 */
	public function message ($message)
	{
		$this->add ('message', $message);
	}

	/**
	 * Adds 'notice' message.
	 */
	public function notice ($message)
	{
		$this->add ('notice', $message);
	}

	/**
	 * Adds generic message.
	 */
	public function add ($type, $message)
	{
		$a = $this->session->get ('__flash_'.$type);
		$a[] = $message;
		$this->session->set ('__flash_'.$type, $a);
	}

	/**
	 * Returns array of flash messages and removes them from session.
	 */
	public function pull_flashes()
	{
		$w = $this->session->delete ('__flash_warning');
		$m = $this->session->delete ('__flash_message');
		$n = $this->session->delete ('__flash_notice');

		return array (
			'warning'	=> coalesce ($w, array()),
			'message'	=> coalesce ($m, array()),
			'notice'	=> coalesce ($n, array()),
		);
	}
}


Fails::$controller->flash = new Flash();

?>
