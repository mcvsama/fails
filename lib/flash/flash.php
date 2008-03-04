<?php # vim: set fenc=utf8 ts=4 sw=4:

class Flash extends Library
{
	public function warning ($string)
	{ }

	public function message ($string)
	{ }

	public function notice ($string)
	{ }
}


Fails::$controller->flash = new Flash();

?>
