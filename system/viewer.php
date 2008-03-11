<?php # vim: set fenc=utf8 ts=4 sw=4:

abstract class Viewer implements CallCatcher
{
	/**
	 * Processes template and returns result as string.
	 */
	abstract public function process();

	##
	## Short-links to View's methods.
	##

	public function render_action ($action, $layout = null, $status = null)
	{
		return Fails::$controller->render_action ($action, $layout, $status);
	}

	public function render_template ($template_name, $layout = null, $status = null)
	{
		return Fails::$controller->render_template ($template_name, $layout, $status);
	}

	public function render_file ($file_name, $layout = false, $status = null)
	{
		return Fails::$controller->render_file ($file_name, $layout, $status);
	}

	public function render_text ($text, $layout = null, $status = null)
	{
		return Fails::$controller->render_text ($text, $layout, $status);
	}

	public function render_json ($object, $status = null)
	{
		return Fails::$controller->render_json ($object, $status);
	}

	/**
	 * TODO opis
	 * To be called only from within a view.
	 */
	public function render_partial ($partial_name, array $locals = array(), $layout = null, $status = null)
	{
		# TODO
	}

	/**
	 * Call catcher.
	 */
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
