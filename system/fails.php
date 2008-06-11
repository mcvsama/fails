<?php # vim: set fenc=utf8 ts=4 sw=4:

class Fails
{
	public static $controller;
	public static $dispatcher;
	public static $logger;
	public static $request;
	public static $response;
	public static $router;
	public static $session;
	public static $config;

	public static function get_state()
	{
		$r = array();
		if (Fails::$request !== null)
		{
			$r['Pre-routing GET parameters'] = array_to_string (Fails::$request->g);
			$r['Pre-routing POST parameters'] = array_to_string (Fails::$request->p);
		}
		if (Fails::$dispatcher->merged_params !== null)
			$r['Post-routing parameters'] = array_to_string (Fails::$dispatcher->merged_params);
		if (Fails::$session !== null)
			$r['Session dump'] = array_to_string (Fails::$session->get_all());
		if (Fails::$request !== null)
			$r['Environment'] = array_to_string (Fails::$request->env);
		return $r;
	}
}

?>
