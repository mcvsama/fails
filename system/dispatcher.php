<?php # vim: set fenc=utf8 ts=4 sw=4:

class Dispatcher
{
	public $logger;
	public $session;
	public $request;
	public $response;
	public $router;

	# Controller and action names:
	public $controller_name;
	public $action_name;

	# Current route got from Router:
	public $current_route;

	# Merged parameters from POST, GET and Route:
	public $merged_params;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->load_files();
		$this->prepare_environment();
		$this->setup_error_handling();
		$this->load_libraries();
		$this->call_action();
	}

	/**
	 * Loads library with given name.
	 */
	public function load_library ($name)
	{
		$this->require_file (FAILS_ROOT.'/lib/'.$name.'/'.$name.'.php');
	}

	/**
	 * Dispatches uncaught call to proper object, if possible.
	 */
	public function catch_call ($name, $arguments)
	{
		# Router:
		if (Fails::$router->can_call ($name, $arguments))
			return Fails::$router->call ($name, $arguments);
		# View:
		else if (Fails::$view->can_call ($name, $arguments))
			return Fails::$view->call ($name, $arguments);

		throw new MethodMissingException ($name, $this);
	}

	##
	## Privates
	##

	/**
	 * Loads files with functions and classes that are needed for work.
	 */
	private function load_files()
	{
		# Load system functions:
		$this->require_files_from_list (FAILS_ROOT.'/system/functions/FUNCTIONS');
		# Load system classes:
		$this->require_files_from_list (FAILS_ROOT.'/system/CLASSES');
		# Load core configurations:
		# TODO
	}

	/**
	 * Initializes environment/system objects, such as Request, Response, Flash, Logger, etc.
	 */
	private function prepare_environment()
	{
		#
		# PHP config
		#

		set_magic_quotes_runtime (0);
		# TODO set_error_handler ('php_error_handler', E_ALL);
		ini_set ('arg_separator.output', '&amp;');
		ini_set ('display_errors', 0);
		ini_set ('log_errors', 1);
		ini_set ('session.name', '_sessid');
		ini_set ('expose_php', 0);
		ini_set ('short_open_tag', 1);
		ini_set ('default_charset', 'UTF-8');

		# Show only fatal errors:
		error_reporting (E_ALL);
		# TODO date_default_timezone_set (CONFIG_DEFAULT_TIMEZONE);

		# Unset deprecated superglobals:
		$HTTP_GET_VARS		= null;
		$HTTP_POST_VARS		= null;
		$HTTP_COOKIE_VARS	= null;
		$HTTP_POST_FILES	= null;
		$HTTP_SERVER_VARS	= null;

		#
		# Helper/global objects
		#

		Fails::$dispatcher = $this;

		# Logger:
		Fails::$logger = $this->logger = new Logger (FAILS_ROOT.'/log/default');

		# Session:
		Fails::$session = $this->session = new Session();

		# Request:
		Fails::$request = $this->request = new Request();

		# Response:
		Fails::$response = $this->response = new Response();

		# Router:
		Fails::$router = $this->router = new Router();
		$this->require_file (FAILS_ROOT.'/config/routes.inc');
		$this->current_route = $this->router->get_route_for ($this->request->route_string());

		# Controller/action names:
		$this->merged_params = array_merge ($this->current_route->get_params(), $this->request->g, $this->request->p);
		$this->controller_name = $this->merged_params['controller'];
		$this->action_name = $this->merged_params['action'];

		# View:
		Fails::$view = $this->view = new View();

		# Load controller file:
		Fails::$controller = $this->controller = $this->load_controller();
	}

	/**
	 * Sets up framework error handling.
	 */
	private function setup_error_handling()
	{
		# TODO
	}

	/**
	 * Loads controller with security checks.
	 */
	private function load_controller()
	{
		$b = FAILS_ROOT.'/app/controllers/';
		$f = realpath ($b.$this->controller_name.'_controller.php');
		# Throw exception if $controller_name pointed out of controllers directory:
		if (strpos ($f, $b) !== 0)
			throw new MissingControllerException ("invalid controller name '{$this->controller_name}'");
		# Load file if everything seems OK:
		$this->require_file ($f);
		# Controller:
		$class_name = Inflector::to_controller_name ($this->controller_name);
		if (!class_exists ($class_name))
			throw new MissingControllerException ("couldn't find controller '{$class_name}'");
		return new $class_name();
	}

	/**
	 * Calls action on loaded controller.
	 */
	private function call_action()
	{
		/* TODO this idea:
		Controller::action_wrapper()
		{
		  $this->action();
		}

		# Overwrite:
		function action_wrapper()
		{
		  try {
			$this->action();
		  }
		  catch (...)
		  {
		  }
		}
		*/

		$method_name = Inflector::to_action_name ($this->action_name);
		if (!method_exists ($this->controller, $method_name))
			throw new MissingActionException ("couldn't find action '{$method_name}'");

		# Before-filter:
		$bf = true;
		if (method_exists ($this->controller, 'before_filter'))
			$bf = $this->controller->before_filter();

		# Call action:
		if ($bf !== false)
			$this->controller->$method_name();

		# After-filter:
		if (method_exists ($this->controller, 'after_filter'))
			return $this->controller->after_filter();

		# Echo rendered result: TODO run Response->respond() or similar.
		echo $this->view->content_for_layout();
	}

	/**
	 * Loads libraries.
	 */
	private function load_libraries()
	{
		$file_name = FAILS_ROOT.'/lib/LIBRARIES';
		foreach (file ($file_name) as $line)
			if (($stripped = trim ($line, " \n\r\v\t")) != '')
				$this->load_library ($stripped);
	}

	/**
	 * Loads files from list-file.
	 *
	 * \throws	RequireFileException
	 * 			On first file from list that could not be loaded.
	 */
	private function require_files_from_list ($file_name)
	{
		$directory = dirname ($file_name);
		foreach (file ($file_name) as $line)
			if (($stripped = trim ($line)) != '')
				$this->require_file (rtrim ($directory, '/').'/'.$stripped.'.php');
	}

	/**
	 * Loads file by given name.
	 *
	 * \throws	RequireFileException
	 * 			When file could not be loaded.
	 */
	private function require_file ($file_name)
	{
		if ((include_once $file_name) === false)
			throw new RequireFileException ("couldn't load file '".basename ($file_name)."'");
	}
}

?>
