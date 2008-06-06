<?php # vim: set fenc=utf8 ts=4 sw=4:

# This is a Fails' Front Controller.
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
		try {
			$this->load_files();
			$this->check_config();
			$this->prepare_environment();
			$this->setup_error_handling();
			$this->load_libraries();
			$this->call_action();
		}
		catch (Exception $e)
		{
			$this->render_exception ($e);
		}
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
		# Controller:
		else if (Fails::$controller->can_call ($name, $arguments))
			return Fails::$controller->call ($name, $arguments);

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
		# Load cascading configurations:
		$this->require_file (FAILS_ROOT.'/config/environment.inc');
		# TODO
	}

	/**
	 * Checks if all required config values are set.
	 */
	private function check_config()
	{
		$e = array();
		if (!isset (Fails::$config->fails))
			$e[] = 'Fails::$config->fails not set';
		else
		{
			$properties = array ('render_exceptions', 'auto_rendering', 'error_404_file', 'error_500_file');
			$error_files = array ('error_404_file', 'error_500_file');

			foreach (array_merge ($properties, $error_files) as $v)
				if (!isset (Fails::$config->fails->$v))
					$e[] = '• <code>Fails::$config->fails->'.$v.'</code> not set';

			foreach ($error_files as $v)
				if (isset (Fails::$config->fails->$v) && @file_get_contents (FAILS_ROOT.'/public/'.Fails::$config->fails->$v) === false)
					$e[] = '• error file <code>'.Fails::$config->fails->$v.'</code> defined by <code>Fails::$config->fails->'.$v.'</code> is not accessible';
		}

		if (count ($e))
		{
			$this->render_error ('Configuration error', implode ('<br>', $e));
			die();
		}
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
			throw new MissingControllerException ("couldn't find controller '{$this->controller_name}'");
		# ApplicationController, Helper, Model, Presenter:
		$this->require_file (FAILS_ROOT.'/app/models/application_model.php');
		$this->require_file (FAILS_ROOT.'/app/helpers/application_helper.php');
		$this->require_file (FAILS_ROOT.'/app/controllers/application_controller.php');
		$this->require_file (FAILS_ROOT.'/app/presenters/application_presenter.php');
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
		$method_name = Inflector::to_action_name ($this->action_name);
		if (!method_exists ($this->controller, $method_name))
			throw new MissingActionException ("couldn't find action '{$this->action_name}'");

		# Call action:
		$this->controller->do_action ($method_name);

		# Set content for response:
		$this->response->set_content ($this->controller->content_for_layout());

		# Echo rendered result:
		$this->response->answer();
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

	private function render_exception (Exception $e)
	{
		if (Fails::$config->fails->render_exceptions)
		{
			header ('Status: 500 Internal server error: uncaught exception');
			header ('Content-Type: text/html; charset=UTF-8');
			$v = @file_get_contents (FAILS_ROOT.'/system/views/exception.php');
			if ($v === false)
				throw new Exception ('internal error: could not load exception view');
			$r = eval ("?>$v<?php ");
			if ($r === false)
				throw new Exception ('internal error: error in exception view');
			echo $r;
		}
		else
		{
			if ($e instanceof RouteNotFoundException)
			{
				$s = '404 Not found';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_404_file;
			}
			else
			{
				$s = '500 Internal server error';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_500_file;
			}

			header ('Status: '.$s);
			header ('Content-Type: text/html; charset=UTF-8');

			# File existence has been asserted in check_config().
			echo file_get_contents ($f);
		}
	}

	private function render_error ($header_html, $content_html)
	{
		header ('Content-Type: text/html; charset=UTF-8');
		echo "<h1>$header_html</h1>";
		echo "<p>$content_html</p>";
	}
}

?>
