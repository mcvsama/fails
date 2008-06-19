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

	/**
	 * Returns Fails main state information
	 * as a human readable map of string => string.
	 */
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

	/**
	 * Executes given PHP code.
	 *
	 * \throws	ParserException
	 * 			When code is invalid or returns false.
	 */
	public static function protect_code ($code)
	{
		$r = eval ($code);
		if ($r === false)
			throw new ParserException ('PHP parse error');
		return $r;
	}

	/**
	 * Loads files from list-file.
	 *
	 * \throws	RequireFileException
	 * 			On first file from list that could not be loaded.
	 */
	public static function require_files_from_list ($file_name)
	{
		$directory = dirname ($file_name);
		foreach (file ($file_name) as $line)
			if (($stripped = trim ($line)) !== '')
				Fails::require_file (rtrim ($directory, '/').'/'.$stripped.'.php');
	}

	/**
	 * Loads file by given name.
	 *
	 * \throws	RequireFileException
	 * 			When file could not be loaded.
	 * \throws	ParserException
	 * 			When loaded file has syntax errors.
	 */
	public static function require_file ($file_name)
	{
		if (($c = @file_get_contents ($file_name)) === false)
			throw new RequireFileException ("couldn't load file '".basename ($file_name)."'");
		Fails::protect_code ('?>'.$c.'<?php ');
	}
}

?>
