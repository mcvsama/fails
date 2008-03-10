<?php # vim: set fenc=utf8 ts=4 sw=4:

class View implements DynamicMethod, CallCatcher
{
	# Factories creating ViewProcessors:
	private $factories;

	# Map of named blocks of views:
	private $content_for;

	# Identifier of engine to use:
	private $current_factory;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->factories = array();
		$this->content_for = array();
		$this->current_factory = null;
	}

	/**
	 * Registers new ViewProcessorFactory.
	 * This function is intended to use only by templating engines
	 * for registering themselves in Fails.
	 *
	 * \throws	ViewEngineAlreadyRegisteredException
	 * 			If engine with same identifier is already registered.
	 */
	public function register_factory (ViewProcessorFactory $factory)
	{
		if (isset ($this->factories[$factory->identifier()]))
			throw new ViewEngineAlreadyRegisteredException ($factory);
		$this->factories[$factory->identifier()] = $factory;
	}

	/**
	 * Sets current templating engine to use.
	 * If only one templating engine is loaded, there is no need to call this
	 * function.
	 *
	 * \param	identifier
	 * 			View engine identifier.
	 *
	 * \throws	MissingTemplatingEngineException
	 * 			When selected templating engine haven't been loaded/registered.
	 */
	public function use_engine ($identifier)
	{
		if (!isset ($this->factories[$identifier]))
			throw new MissingTemplatingEngineException ($identifier);
		$this->current_factory = $this->factories[$identifier];
	}

	/**
	 * Renders an action template.
	 *
	 * \param	action
	 * 			Template name (without extension) relative to current controller's templates directory.
	 * \param	layout
	 * 			Layout name. If null or true - current layout is used. If false - no layout is used.
	 * \param	status
	 * 			Response status string or integer. If null, default is applied (depends on Response object,
	 * 			mostly it will be "200 OK" or for example "304 Not Modified", etc.).
	 *
	 * \throws	MissingViewException
	 * 			When given template file can't be found or loaded.
	 */
	public function render_action ($action, $layout = null, $status = null)
	{
		return $this->render_template (Fails::$dispatcher->controller_name.'/'.$action, $layout, $status);
	}

	/**
	 * Renders template.
	 *
	 * \param	template_name
	 * 			Template name (without extension) relative to templates root directory.
	 *
	 * Other parameters as in render_action().
	 *
	 * \throws	MissingViewException
	 * 			When given template file can't be found or loaded.
	 */
	public function render_template ($template_name, $layout = null, $status = null)
	{
		$factory = $this->get_factory();
		$file_name = FAILS_ROOT.'/app/views/'.$template_name.'.'.$factory->extension();
		return $this->render_file ($file_name, $layout, $status);
	}

	/**
	 * \param	file_name
	 * 			Absolute template file name with extension.
	 *
	 * Other parameters as in render_action().
	 *
	 * \throws	MissingViewException
	 * 			When given template file can't be found or loaded.
	 */
	public function render_file ($file_name, $layout = false, $status = null)
	{
		# Load template file:
		$content = @file_get_contents ($file_name);
		if ($content === false)
			throw new MissingViewException ("View template missing: '".$file_name."'");
		$processor = $this->get_processor ($content, Fails::$controller->get_variables_for_view());
		return $this->render_text ($processor->process(), $layout, $status);
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
	 * TODO opis
	 */
	public function render_text ($text, $layout = null, $status = null)
	{
		# TODO przekazywać wynik do response
		if ($layout === false)
			return $this->content_for['layout'] = $this->content_for['action'] = $text;
		else
		{
			$this->content_for['action'] = $text;
			return $this->content_for['layout'] = $this->render_template ('layouts/user', false, $status);
		}
	}

	/**
	 * TODO opis
	 */
	public function render_json ($object, $status = null)
	{
		# TODO Use json::encode()/decode from infopedia/php-framework
	}

	/**
	 * Prevents setting rendered output as response content.
	 */
	public function prevent_rendering_as_response()
	{
		# TODO
	}

	##
	## Privates
	##

	/**
	 * Returns appropriate processor factory object to use.
	 *
	 * \throws	ViewConfigurationException
	 * 			When there is more than one templating engine registered
	 * 			and no one has been seleted with use_engine() method.
	 */
	private function get_factory()
	{
		if ($this->current_factory === null)
		{
			if (count ($this->factories) == 1)
			{
				$z = array_values ($this->factories);
				return $this->current_factory = $z[0];
			}
			else
				throw new ViewConfigurationException ('no templating engine has been loaded');
		}
		return $this->current_factory;
	}

	/**
	 * Returns appropriate processor object to use.
	 */
	private function get_processor ($content, array $variables)
	{
		# TODO jeśli jest tylko jeden silnik, wybierz go. W przeciwnym razie rządaj określenia przez
		# kontroler jakiego silnika używać.
		return $this->get_factory()->instantiate ($content, $variables);
	}

	##
	## Interface DynamicMethod
	##

	public function can_call ($name, $arguments)
	{
		return preg_match ('/^content_for_(.+)$/', $name, $out);
	}

	public function call ($name, $arguments)
	{
		if (preg_match ('/^content_for_(.+)$/', $name, $out))
			return @$this->content_for[$out[1]];
		throw new MethodMissingException ($name, $this);
	}

	/**
	 * Call catcher.
	 */
	public function __call ($name, $arguments)
	{
		return Fails::$dispatcher->catch_call ($name, $arguments);
	}
}


abstract class ViewProcessorFactory
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


abstract class ViewProcessor implements CallCatcher
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
		return Fails::$view->render_action ($action, $layout, $status);
	}

	public function render_template ($template_name, $layout = null, $status = null)
	{
		return Fails::$view->render_template ($template_name, $layout, $status);
	}

	public function render_file ($file_name, $layout = false, $status = null)
	{
		return Fails::$view->render_file ($file_name, $layout, $status);
	}

	public function render_partial ($partial_name, array $locals = array(), $layout = null, $status = null)
	{
		return Fails::$view->render_partial ($partial_name, $locals, $layout, $status);
	}

	public function render_text ($text, $layout = null, $status = null)
	{
		return Fails::$view->render_text ($text, $layout, $status);
	}

	public function render_json ($object, $status = null)
	{
		return Fails::$view->render_json ($object, $status);
	}

	/**
	 * Call catcher.
	 */
	public function __call ($name, $arguments)
	{
		return Fails::$dispatcher->catch_call ($name, $arguments);
	}
}

?>
