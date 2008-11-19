<?php # vim: set fenc=utf8 ts=4 sw=4:

class Router implements DynamicMethod, CallCatcher
{
	# List of routes:
	private $routes;
	private $routes_by_name;

	# Used for default params for 'with/without' block:
	private $default_params_stack;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->routes = array();
		$this->routes_by_name = array();
		$this->default_params_stack = array();
	}

	/**
	 * Adds new route.
	 *
	 * \param	name
	 * 			Name of the route. Can be null (anonymous route).
	 * 			If name is given, it will be possible to call dynamic method $router->name_url (array $params)
	 * 			which will return Route object.
	 * \param	path
	 * 			User-Agent URL path, ie path visible to the user in URL input.
	 * 			Can contain parameters leaded with ':', ie. 'albums/:album_nr'.
	 * 			Some parameters may have special meaning for Fails, ie. 'controller' and
	 * 			'action' will be used by dispatcher to find proper controller and method to
	 * 			execute.
	 * 			Note: characters allowed for parameter name in path are [-_0-9a-zA-Z].
	 * \param	params
	 * 			Map of default values for params. Keys must not contain leading ':'.
	 * 			If parameter is absent it is considered to be required.
	 * 			If parameter is present it is considered to be optional unless
	 * 			some other required parameter stands after this optional in path parameter
	 * 			(a warning will be issued in that case). Value for key denotes default value
	 * 			for optional parameter.
	 *
	 * 			Example:
	 * 			 * $router->route (null, ':controller/:action')
	 * 			   Anonymous route which for URL 'abc/xyz' will yield parameters:
	 * 			   * 'controller' => 'abc', required
	 * 			   * 'action' => 'xyz', required
	 *
	 * 			 * $router->route (null, 'user/:username/:action', array ('action' => 'show')
	 * 			   Anonymous route which for URL 'user/testuser/remove' yield parameters:
	 * 			   * 'username' => 'testuser', required
	 * 			   * 'action' => 'remove', optional, defaults to 'show'
	 */
	public function connect ($name, $path, array $params = array())
	{
		if ($name !== null && array_key_exists ($name, $this->routes_by_name))
			throw new DuplicateRouteException ($name);
		$route = new Route ($name, $path, array_merge ($this->current_default_params(), $params));
		$this->routes[] = $route;
		$this->routes_by_name[$name] = $route;
	}

	/**
	 */
	public function with (array $params)
	{
		$this->default_params_stack[] = array_merge ($this->current_default_params(), $params);
	}

	/**
	 */
	public function without()
	{
		array_pop ($this->default_params_stack);
	}

	/**
	 * Working around PHP's project decisions:
	 */
	private function current_default_params()
	{
		$x = end ($this->default_params_stack);
		if (!$x)
			$x = array();
		return $x;
	}

	/**
	 * Tests given relative URL agains all registered routes and returns first
	 * matching route.
	 *
	 * \param	url
	 * 			URL to match.
	 * \throws	RouteNotFoundException
	 * 			When no route matches given URL.
	 * \returns	Route object matching given URL.
	 */
	public function get_route_for ($url)
	{
		foreach ($this->routes as $route)
			if ($route->match ($url))
				return $route;
		throw new RouteNotFoundException ($url);
	}

	/**
	 * \returns	value returned by Route::generate_url().
	 */
	public function generate_url ($name, array $params = array())
	{
		if (!array_key_exists ($name, $this->routes_by_name))
			throw new RouteGenerationException ("couldn't find route with name '$name'");
		return $this->routes_by_name[$name]->generate_url ($params);
	}

	/**
	 * \returns URL for given parameters.
	 *			To use when no named route is defined.
	 *
	 * \param	route_segments
	 * 			Route segments string, i.e. 'controller1/action3/:param'.
	 * \param	params
	 * 			Map of parameters for this route.
	 */
	public function url_for ($route_segments, array $params = array())
	{
		$r = new Route (null, $route_segments, $params);
		return $r->generate_url ($params);
	}

	##
	## Interface DynamicMethod
	##

	public function can_call ($name, $arguments)
	{
		if (preg_match ('/^(.+)_url$/', $name, $out))
			return isset ($this->routes_by_name[$out[1]]);
		return false;
	}

	public function call ($method, $arguments)
	{
		if (preg_match ('/^(.+)_url$/', $method, $out))
			return $this->generate_url ($out[1], coalesce (@$arguments[0], array()));
		throw new MethodMissingException ($method, $this);
	}

	/**
	 * Call catcher.
	 */
	public function __call ($name, $arguments)
	{
		return Fails::$dispatcher->catch_call ($name, $arguments);
	}
}


class Route
{
	public $name;
	public $path;
	public $params;

	# Path break down into segments:
	private $segments;
	private $matched_params;

	/**
	 * Ctor
	 *
	 * \param	name
	 * 			Route name
	 * \param	path
	 * 			URL path
	 * \param	params
	 * 			Route parameters
	 * \throws	RoutePathInvalidException
	 * 			When given route path has invalid format.
	 */
	public function __construct ($name, $path, array $params = null)
	{
		$this->name = $name;
		$this->path = trim ($path, '/ ');
		$this->params = $params;
		$this->matched_params = null;

		$this->segments = array();
		$this->param_segments = array();

		# Set for checking if parameter names in path do not repeat:
		$uniq = array();

		foreach (explode ('/', $this->path) as $s)
		{
			$seg = new stdclass();
			if (isset ($s[0]) && $s[0] == ':')
			{
				$seg->type = 'param';
				$seg->name = substr ($s, 1);
				# Check if parameter name is not duplicated:
				if (in_array ($seg->name, $uniq))
					throw new RoutePathInvalidException ($this, 'duplicated parameter name in path expression');
				# Add default value, if parameter is optional:
				if (array_key_exists ($seg->name, $this->params))
				{
					$seg->default = true;
					$seg->value = $this->params[$seg->name];
				}
				else
					$seg->default = false;
				$uniq[] = $seg->name;
				$this->segments[] = $seg;
				$this->param_segments[] = $seg;
			}
			else
			{
				$seg->type = 'string';
				$seg->value = $s;
				$this->segments[] = $seg;
			}
		}

		# Ensure that no optional segment precedes required segment:
		$r = false;
		for ($i = count ($this->segments)-1; $i >= 0; --$i)
		{
			if ($this->segments[$i]->type == 'param')
			{
				if ($this->segments[$i]->default && $r)
				{
					$seg = $this->segments[$i];
					Fails::$logger->warn ("Optional segment '{$seg->name}' in route '{$this->name}' precedes required segment - making '{$seg->name}' required.");
					$this->segments[$i]->default = false;
				}
				else if (!$this->segments[$i]->default)
					$r = true;
			}
		}
	}

	/**
	 * Checks if given URL matches this route.
	 *
	 * \returns	true or false.
	 */
	public function match ($url)
	{
		$url = trim ($url, '/ ');
		if (preg_match ('/^'.$this->to_regexp().'$/', $url, $out))
		{
			$this->matched_params = array();
			array_shift ($out);
			for ($i = 0; $i < count ($this->param_segments); ++$i)
			{
				$seg = $this->param_segments[$i];
				$val = coalesce (@$out[$i], @$this->params[$seg->name]);
				if (is_string ($val))
					$val = trim ($val, '/');
				$this->matched_params[$seg->name] = $val;
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns map of route-parameters.
	 */
	public function get_params()
	{
		return array_merge ($this->params, $this->matched_params);
	}

	/**
	 * Generates URL for direct use in view.
	 */
	public function generate_url (array $params = array())
	{
		$missing_parameters = array();
		$used_parameters = array();
		$p = array_merge ($this->params, $params);
		$s = '';
		foreach ($this->segments as $seg)
		{
			if ($seg->type == 'string')
				$s .= '/'.urlencode ($seg->value);
			else if ($seg->type == 'param')
			{
				if (isset ($p[$seg->name]))
				{
					$s .= '/'.urlencode ($p[$seg->name]);
					$used_parameters[] = $seg->name;
				}
				else
					$missing_parameters[] = $seg->name;
			}
		}
		# If there are missing parameters:
		if (count ($missing_parameters))
			throw new RouteGenerationException ("couldn't generate url from route '{$this->name}': missing parameters: ".implode (', ', $missing_parameters));
		# Additional parameters:
		$additional_params = array_diff (array_keys ($params), $used_parameters);
		if (count ($additional_params))
		{
			$s .= '?';
			$z = array();
			foreach ($additional_params as $par_name)
				$z[] = urlencode ($par_name).'='.urlencode ($params[$par_name]);
			$s .= implode ('&', $z);
		}
		return Fails::$request->fully_qualified_base_url().$s;
	}

	##
	## Privates
	##

	/**
	 * Generates regular expression used to check if
	 * url matches this route.
	 *
	 * \returns	string without leading and trailing '/'s.
	 */
	private function to_regexp()
	{
		$re = '';
		foreach ($this->segments as $seg)
		{
			if ($seg->type == 'param')			$re .= ($re? '(\/[^\/]+)' : '([^\/]+)').($seg->default? '?' : '');
			else if ($seg->type == 'string')	$re .= ($re? '\/' : '').preg_quote ($seg->value, '/');
			else								throw new Exception ("router: internal error: unexpected segment of type '{$seg->type}'");
		}
		return $re;
	}
}

?>
