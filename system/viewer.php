<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class Viewer implements CallCatcher
{
	/**
	 * Processes template and returns result as string.
	 */
	abstract public function process();

	##
	## Interface CallCatcher
	##

	public function __call ($name, $arguments)
	{
		return Fails::$dispatcher->catch_call ($name, $arguments);
	}
}


abstract class ViewerFactory
{
	/**
	 * Returns engine identifier.
	 */
	abstract public function identifier();

	/**
	 * Returns template files extension.
	 */
	abstract public function extension();

	/**
	 * Creates new view processor.
	 *
	 * \param	content
	 * 			Template content.
	 * \param	variables
	 * 			Map of variables passed to template.
	 */
	abstract public function instantiate ($content, array $variables);
}

?>
