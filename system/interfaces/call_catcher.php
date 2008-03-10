<?php # vim: set fenc=utf8 ts=4 sw=4:

interface CallCatcher
{
	/**
	 * Should call Fails::$dispatcher->catch_call().
	 */
	public function __call ($name, $arguments);
}

?>
