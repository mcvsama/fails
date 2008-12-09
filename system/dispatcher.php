<?php # vim: set fenc=utf8 ts=4 sw=4:

# This is Fails' Front Controller.
class Dispatcher
{
	public $logger;
	public $anomalies;
	public $session;
	public $request;
	public $response;
	public $router;
	public $cache;

	# Application, controller and action names:
	public $application_name;
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
			$this->load_system_files();
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

	/**
	 * Returns application's root directory name.
	 */
	public function application_root()
	{
		return FAILS_ROOT.'/app'.($this->application_name ? ":".$this->application_name : '');
	}

	##
	## Privates
	##

	/**
	 * Loads files with functions and classes that are needed for work.
	 */
	private function load_system_files()
	{
		# TODO Caching loaded files. Barrier is mtime of file list (FUNCTIONS, CLASSES, …)
		# Load system functions:
		$this->require_files_from_list (FAILS_ROOT.'/system/functions/FUNCTIONS');
		# Load system classes:
		$this->require_files_from_list (FAILS_ROOT.'/system/CLASSES');
		# Load configuration and environment:
		$this->require_file (FAILS_ROOT.'/config/config.inc');
		$envdir = FAILS_ROOT.'/config/environments/'.Fails::$config->fails->environment;
		$this->require_file ($envdir.'/config.inc');
		# Load other .inc files:
		$dir = @opendir ($envdir);
		if ($dir !== false)
			while (($entry = readdir ($dir)) !== false)
				if ($entry !== 'config.inc')
					if (is_file ("$envdir/$entry"))
						$this->require_file ("$envdir/$entry");
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
			$properties = array ('render_exceptions', 'display_errors', 'auto_rendering', 'error_403_file', 'error_404_file', 'error_422_file', 'error_500_file');
			$error_files = array ('error_403_file', 'error_404_file', 'error_422_file', 'error_500_file');

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
		ini_set ('arg_separator.output', '&amp;');
		ini_set ('display_errors', Fails::$config->fails->display_errors? 1 : 0);
		ini_set ('log_errors', 1);
		ini_set ('expose_php', 0);
		ini_set ('short_open_tag', 1);
		ini_set ('default_charset', 'UTF-8');
		ini_set ('session.name', Fails::$config->fails->session->id);
		error_reporting (Fails::$config->fails->display_errors_threshold);

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

		# Loggers:
		Fails::$logger = $this->logger = new Logger (FAILS_ROOT.'/log/default.'.date('Y-m-d'));
		Fails::$anomalies = $this->anomalies = new Logger (FAILS_ROOT.'/log/anomalies.'.date('Y-m-d'));

		# Session:
		Fails::$session = $this->session = new Session();

		# Cache:
		Fails::$cache = $this->cache = new Cache (FAILS_ROOT.'/tmp/cache');

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
		$this->application_name = @$this->merged_params['application'];
		$this->controller_name = @$this->merged_params['controller'];
		$this->action_name = @$this->merged_params['action'];

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
		$b = $this->application_root().'/controllers/';
		$f = realpath ($b.$this->controller_name.'_controller.php');
		# Throw exception if $controller_name pointed out of controllers directory:
		if (strpos ($f, $b) !== 0)
			throw new MissingControllerException ("couldn't find controller '{$this->controller_name}'");
		# ApplicationController:
		$this->require_file ($this->application_root().'/controllers/application_controller.php');
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
		$this->add_runtime_control_point();
		$this->controller->do_action ($method_name);
		$this->add_runtime_control_point();

		# Set content for response, if something has been rendered:
		$body = $this->controller->content_for_layout();
		if ($body !== null)
			$this->response->set_body ($body);

		# Runtime:
		$this->compute_runtime();

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
			$s = '500 Internal server error';
			if ($e instanceof StatusException)
				$s = $e->status_code.' '.$e->getMessage();
			header ('HTTP/1.1 '.$s);
			if ($this->request->is_xhr())
			{
				header ('Content-Type: text/plain; charset=UTF-8');
				$v = @file_get_contents (FAILS_ROOT.'/system/views/exception_xhr.php');
			}
			else
			{
				header ('Content-Type: text/html; charset=UTF-8');
				$v = @file_get_contents (FAILS_ROOT.'/system/views/exception.php');
			}
			if ($v === false)
				throw new Exception ('internal error: could not load exception view');
			$r = eval ("?>$v<?php ");
			if ($r === false)
				throw new Exception ('internal error: error in exception view');
			echo $r;
		}
		else
		{
			if ($e instanceof RouteNotFoundException || ($e instanceof StatusException && $e->status_code == 404))
			{
				$s = '404 Not found';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_404_file;
			}
			else if ($e instanceof StatusException && $e->status_code == 403)
			{
				$s = '403 Forbidden';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_403_file;
			}
			else if ($e instanceof StatusException && $e->status_code == 422)
			{
				$s = '422 Unprocessable Entity';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_422_file;
			}
			else
			{
				$s = '500 Internal server error';
				$f = FAILS_ROOT.'/public/'.Fails::$config->fails->error_500_file;
			}

			header ('HTTP/1.1 '.$s);
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

	private function add_runtime_control_point()
	{
		global $runtime_control_points;
		$runtime_control_points[] = microtime();
	}

	private function compute_runtime()
	{
		global $runtime_control_points;
		if (!is_array ($runtime_control_points))
			die();
		$runtimes = array();
		for ($i = 1; $i < count ($runtime_control_points); ++$i)
		{
			list ($usec1, $sec1) = explode (' ', $runtime_control_points[$i-1]);
			list ($usec2, $sec2) = explode (' ', $runtime_control_points[$i-0]);
			$diff = ((float)$usec2 + (float)$sec2) - ((float)$usec1 + (float)$sec1);
			$diff = round ($diff * 1000) / 1000.0;
			$runtimes[] = $diff;
		}
		$total_runtime = 0;
		foreach ($runtimes as $x)
			$total_runtime += $x;
		$percentages = array();
		foreach ($runtimes as $x)
			$percentages[] = round ($x / $total_runtime * 1000) / 10.0;
		$s = array();
		for ($i = 0; $i < count ($runtimes); ++$i)
			$s[] = $runtimes[$i].'s/'.$percentages[$i].'%';
		$runtime = $total_runtime.'s = '.(round (1.0 / $total_runtime * 10) / 10). ' reqs/sec ('.join (', ', $s).')';
		Fails::$logger->add (Logger::CLASS_INFO, 'Runtime: '.$runtime);
		if (Fails::$config->fails->debug_info === true)
			$this->response->set_header ('X-Runtime', $runtime);
	}
}

?>
