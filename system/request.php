<?php # vim: set fenc=utf8 ts=4 sw=4:

class Request
{
	# GET, POST:
	public $g;
	public $p;

	# Environment variables:
	public $env;

	# Privates:
	private $route_string;
	private $base_url;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->g = $_GET;
		$this->p = $_POST;
		$this->env = $_SERVER;

		Fails::$logger->add (Logger::CLASS_INFO, 'Request '.$this->env['REQUEST_METHOD'].' '.$this->env['REQUEST_URI']);

		unset ($_GET);
		unset ($_POST);
		unset ($_SERVER);

		$this->setup_base_url();
		$this->setup_route_string();
	}

	/**
	 * Returns query-string, that is string after the '?' in URL.
	 */
	public function query_string()
	{
		return $this->env['QUERY_STRING'];
	}

	/**
	 * Returns requested route string, without query parameters (those after '?')
	 * and without base-url.
	 */
	public function route_string()
	{
		return $this->route_string;
	}

	/**
	 * Returns complete URL for this request.
	 */
	public function url()
	{
		return $this->fully_qualified_base_url().'/'.$this->route_string().'?'.$this->query_string();
	}

	/**
	 * Returns base URL for this Fails installation without trailing '/'.
	 */
	public function base_url()
	{
		return $this->base_url;
	}

	/**
	 * Returns fully qualified base URL without trailing '/'.
	 */
	public function fully_qualified_base_url()
	{
		return rtrim ($this->protocol().$this->env['SERVER_NAME'].$this->base_url(), '/');
	}

	/**
	 * Returns 'http://' or 'https://' depending on current protocol scheme.
	 */
	public function protocol()
	{
		return $this->scheme().'://';
	}

	/**
	 * Returns 'http' or 'https'.
	 */
	public function scheme()
	{
		return (isset ($this->env['HTTPS']) && strtolower ($this->env['HTTPS']) == 'on')? 'https' : 'http';
	}

	/**
	 * Returns lowercase request method.
	 */
	public function method()
	{
		return strtolower ($this->env['REQUEST_METHOD']);
	}

	/**
	 * Returns true if request method is GET.
	 */
	public function is_get()
	{
		return $this->method() === 'get';
	}

	/**
	 * Returns true if request method is POST.
	 */
	public function is_post()
	{
		return $this->method() === 'post';
	}

	/**
	 * Returns true if request method is PUT.
	 */
	public function is_put()
	{
		return $this->method() === 'put';
	}

	/**
	 * Returns true if request method is DELETE.
	 */
	public function is_delete()
	{
		return $this->method() === 'delete';
	}

	/**
	 * Returns true if request method is HEAD.
	 */
	public function is_head()
	{
		return $this->method() === 'head';
	}

	/**
	 * Returns request port, as integer.
	 */
	public function port()
	{
		return intval (coalesce ($this->env['SERVER_PORT'], 80));
	}

	/**
	 * Returns client IP as string.
	 */
	public function client_ip()
	{
		return $this->env['REMOTE_ADDR'];
	}

	/**
	 * Returns client TCP port number.
	 */
	public function client_port()
	{
		return intval ($this->env['REMOTE_PORT']);
	}

	/**
	 * Returns true if url scheme is 'https'.
	 */
	public function is_secure()
	{
		return $this->scheme() === 'https';
	}

	/**
	 * Returns true if request contains header X-Requested-With with value 'XMLHttpRequest'.
	 * Useful for asynchronous request made with PrototypeJS.org.
	 */
	public function is_async()
	{
		return $this->env['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Synonym to is_async.
	 */
	public function is_xhr()
	{
		return $this->is_async();
	}

	##
	## Privates
	##

	/**
	 */
	private function setup_base_url()
	{
		# Base URL:
		if (Fails::$config->fails->base_url !== null)
			$this->base_url = Fails::$config->fails->base_url;
		else
		{
			# Base URL is common leading part of REQUEST_URI and SCRIPT_NAME variables.
			$this->base_url = '';
			$u = $this->env['REQUEST_URI'];
			$s = dirname ($this->env['SCRIPT_NAME']);
			for ($i = 0; $i < strlen ($u); ++$i)
			{
				if (!isset ($u[$i]) || !isset ($s[$i]) || $u[$i] != $s[$i])
					break;
				$this->base_url .= $u[$i];
			}
			$this->base_url = '/'.trim ($this->base_url, '/ ');
		}
	}

	/**
	 * Base url needs to be set up before calling this method.
	 */
	private function setup_route_string()
	{
		$regex = preg_quote ($this->base_url(), '/').'([^\?]*)(\?.*)?';
		preg_match ('/^'.$regex.'$/', $this->env['REQUEST_URI'], $out);
		# Extract route string from REQUEST_URI:
		$this->route_string = @trim ($out[1], '/');
	}
}

?>
