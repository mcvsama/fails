<?php # vim: set fenc=utf8 ts=4 sw=4:

class Logger
{
	private $file_name;
	private $muted;

	const CLASS_DEBUG	= 'debug';
	const CLASS_INFO	= 'info';
	const CLASS_WARN	= 'warn';
	const CLASS_ERROR	= 'error';
	const CLASS_FATAL	= 'fatal';

	/**
	 * \param	file_name
	 * 			File name to add messages to.
	 */
	public function __construct ($file_name)
	{
		$this->file_name = $file_name;
	}

	/**
	 * Mutes logger.
	 */
	public function mute()
	{
		$this->muted = true;
	}

	/**
	 * Unmutes logger.
	 */
	public function unmute()
	{
		$this->muted = false;
	}

	/**
	 * \param	class
	 * 			Message class. See CLASS_ constants.
	 * \param	message
	 * 			Message to log.
	 */
	public function add ($class, $message)
	{
		if ($this->muted)
			return;

		if (!in_array ($class, Fails::$config->fails->logger->classes))
			return;

		$utime = explode (' ', microtime());
		$usec = ltrim ($utime[0], '0');
		$datestr = date ('Y-m-d H:i:s');
		$datestrusec = $datestr.$usec.date ('O');

		# Open log file for appending:
		$f = @fopen ($this->file_name, 'a');

		if (!$f)
			throw new LoggerException ('could not open log file for appending');

		# Wait for lock:
		while (!flock ($f, LOCK_EX))
			;

		# Write log message:
		$ip = isset ($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : Fails::$request->env['REMOTE_ADDR'];
		fwrite ($f, "$datestr | ".str_pad ($ip, 15)." | ".str_pad ($class, 5)." | $message\n");

		# Release lock:
		flock ($f, LOCK_UN);

		# Close file:
		fclose ($f);
	}

	/**
	 * \param	message
	 * 			Message to log.
	 */
	public function debug ($message)
	{
		$this->add (Logger::CLASS_DEBUG, $message);
	}

	/**
	 * \param	message
	 * 			Message to log.
	 */
	public function info ($message)
	{
		$this->add (Logger::CLASS_INFO, $message);
	}

	/**
	 * \param	message
	 * 			Message to log.
	 */
	public function warn ($message)
	{
		$this->add (Logger::CLASS_WARN, $message);
	}

	/**
	 * \param	message
	 * 			Message to log.
	 */
	public function error ($message)
	{
		$this->add (Logger::CLASS_ERROR, $message);
	}

	/**
	 * \param	message
	 * 			Message to log.
	 */
	public function fatal ($message)
	{
		$this->add (Logger::CLASS_FATAL, $message);
	}

	/**
	 * \param	exception
	 * 			Exception to log.
	 */
	public function exception ($e)
	{
		$s = 'Exception '.get_class ($e).': '.capitalize ($e->getMessage())."\n";
		$s .= "——— Stack trace ———\n";
		$s .= $e->getTraceAsString()."\n";
		foreach (Fails::get_state() as $h => $c)
		{
			$s .= "——— $h ———\n";
			$s .= "$c\n";
		}
		if ($e instanceof LoggerException)
			error_log ($s);
		else
			$this->fatal ($s);
	}
}

?>
