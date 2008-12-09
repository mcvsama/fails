<?php # vim: set fenc=utf8 ts=4 sw=4:

class Controller implements DynamicMethod, CallCatcher
{
	public $controller;
	public $dispatcher;
	public $logger;
	public $request;
	public $response;
	public $router;
	public $session;
	public $cache;

	# Merged params from GET/POST and Router:
	public $params;

	# Object holding variables meant to be passed to view:
	protected $set;

	# Factories creating Viewers:
	private $factories;

	# Map of named blocks of views:
	private $content_for;

	# Identifier of engine to use:
	private $current_factory;

	# Whether autorender should be stopped:
	private $stop_autorender = false;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		assert (Fails::$request !== null);
		assert (Fails::$router !== null);
		assert (Fails::$dispatcher->current_route !== null);

		$this->params = &Fails::$dispatcher->merged_params;
		$this->set = new stdclass();

		$this->factories = array();
		$this->content_for = array();
		$this->current_factory = null;

		$this->dispatcher	= Fails::$dispatcher;
		$this->logger		= Fails::$logger;
		$this->session		= Fails::$session;
		$this->request		= Fails::$request;
		$this->response		= Fails::$response;
		$this->router		= Fails::$router;
		$this->controller	= Fails::$controller;
		$this->cache		= Fails::$cache;
	}

	public function do_action ($method_name)
	{
		try {
			# Configure method:
			if (method_exists ($this, 'configure'))
				$this->configure();

			# Before-filter:
			$bf = true;
			if (method_exists ($this, 'before_filter'))
				$bf = $this->before_filter();

			# Action:
			if ($bf !== false)
				$this->$method_name();

			# Autorendering unless redirected or already rendered:
			if (Fails::$config->fails->auto_rendering && !$this->response->is_redirected() && !$this->stop_autorender)
				if (!isset ($this->content_for['layout']))
					$this->render();

			# After-filter:
			if (method_exists ($this, 'after_filter'))
				$this->after_filter();
		}
		catch (Exception $e)
		{
			if (method_exists ($this, 'rescue_action'))
				$this->rescue_action ($e);
			else
				throw $e;
		}
	}

	/**
	 * Returns an array of parameter values.
	 */
	public function params()
	{
		$r = array();
		foreach (func_get_args() as $name)
			$r[] = @$this->params[$name];
		return $r;
	}

	/**
	 * Registers new ViewerFactory.
	 * This function is intended to use only by templating engines
	 * for registering themselves in Fails.
	 *
	 * \throws	ViewEngineAlreadyRegisteredException
	 * 			If engine with same identifier is already registered.
	 */
	public function register_viewer_factory (ViewerFactory $factory)
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
	public function use_viewer_engine ($identifier)
	{
		if (!isset ($this->factories[$identifier]))
			throw new MissingTemplatingEngineException ($identifier);
		$this->current_factory = $this->factories[$identifier];
	}

	/**
	 * Returns map of variables meant to be passed to view.
	 */
	protected function get_variables_for_view()
	{
		$ary = array();
		foreach (get_object_vars ($this->set) as $k => $v)
			$ary[$k] = $v;
		return $ary;
	}

	##
	## Rendering methods.
	##
	## All render_* methods return rendered content as a string.
	##

	/**
	 * Renders current action template.
	 *
	 * 'layout' and 'status' parameters have the same meaning as in 'render_action' method.
	 */
	protected function render ($layout = null, $status = null)
	{
		return $this->render_action ($this->dispatcher->action_name, $layout, $status);
	}

	/**
	 * Renders an action template.
	 *
	 * \param	action
	 * 			Template name (without extension) relative to current controller's templates directory.
	 * \param	layout
	 * 			If string - layout name to be used. If null or true - current layout is used. If false - no layout is used.
	 * \param	status
	 * 			Response status string or integer. If null, default is applied (depends on Response object,
	 * 			mostly it will be "200 OK" or for example "304 Not Modified", etc.).
	 *
	 * \throws	ViewMissingException
	 * 			When given template file can't be found or loaded.
	 */
	protected function render_action ($action, $layout = null, $status = null)
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
	 * \throws	ViewMissingException
	 * 			When given template file can't be found or loaded.
	 */
	protected function render_template ($template_name, $layout = null, $status = null)
	{
		$factory = $this->get_viewer_factory();
		$file_name = Fails::$dispatcher->application_root().'/views/'.$template_name.'.'.$factory->extension();
		return $this->render_file ($file_name, $layout, $status);
	}

	/**
	 * \param	file_name
	 * 			Absolute template file name with extension.
	 *
	 * Other parameters as in render_action().
	 *
	 * \throws	ViewMissingException
	 * 			When given template file can't be found or loaded.
	 */
	protected function render_file ($file_name, $layout = false, $status = null)
	{
		if ($this->response->is_redirected())
			throw new DoubleRenderException();
		Fails::$logger->add (Logger::CLASS_INFO, "Rendering file: $file_name");
		# Load template file:
		$content = @file_get_contents ($file_name);
		if ($content === false)
			throw new ViewMissingException ("View template missing: '".$file_name."'");
		$processor = $this->get_processor ($content, $this->get_variables_for_view());
		return $this->render_text ($processor->process(), $layout, $status);
	}

	/**
	 * \param	object
	 * 			Any objecto to transform to JSON.
	 */
	protected function render_json ($object, $status = null)
	{
		$this->response->set_content_type ('application/json; charset=UTF-8');
		return $this->render_text (json_encode ($object), false, $status);
	}

	/**
	 * Renders nothing. Clears rendered result
	 * and stops autorendering if endabled.
	 */
	protected function render_nothing()
	{
		$this->render_text (null, false);
		$this->stop_autorender = true;
	}

	/**
	 * This is main rendering method. All other methods end up calling this (directly or indirectly).
	 *
	 * \param	text
	 * 			Text to render or null to render nothing.
	 *
	 * Other parameters as in render_action().
	 */
	protected function render_text ($text, $layout = null, $status = null)
	{
		# Set status:
		if ($status !== null)
			$this->response->set_status ($status);
		# Set content:
		$layout = coalesce ($layout, $this->layout);
		if ($layout === false || $layout === null)
			return $this->content_for['layout'] = $this->content_for['action'] = $text;
		else
		{
			$this->content_for['action'] = $text;
			if (method_exists ($this, 'before_layout'))
				$this->before_layout ($layout);
			return $this->content_for['layout'] = $this->render_template ('layouts/'.$layout, false, $status);
		}
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

	##
	## Privates
	##

	/**
	 * Returns appropriate processor factory object to use.
	 *
	 * \throws	ViewConfigurationException
	 * 			When there is more than one templating engine registered
	 * 			and no one has been seleted with use_viewer_engine() method.
	 */
	private function get_viewer_factory()
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
		return $this->get_viewer_factory()->instantiate ($content, $variables);
	}

	##
	## Interface CallCatcher
	##

	public function __call ($name, $arguments)
	{
		return Fails::$dispatcher->catch_call ($name, $arguments);
	}
}

?>
