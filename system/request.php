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

		unset ($_GET);
		unset ($_POST);
		unset ($_SERVER);

		$this->setup_base_url();
		$this->setup_route_string();
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
	 * Returns base-url for this Fails installation.
	 */
	public function base_url()
	{
		return $this->base_url;
	}

	##
	## Privates
	##

	/**
	 */
	private function setup_base_url()
	{
		# Base URL:
		if (defined ('CONFIG_BASE_URL'))
			$this->base_url = CONFIG_BASE_URL;
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
		$this->route_string = trim ($out[1], '/');
	}
}

?>
