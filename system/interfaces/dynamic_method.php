<?php # vim: set fenc=utf8 ts=4 sw=4:

interface DynamicMethod
{
	/**
	 * \returns	true if method can be called.
	 */
	public function dynamic_method ($name, $params);
}

?>
