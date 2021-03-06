<?php # vim: set fenc=utf8 ts=4 sw=4:

class PostgreSQLConnectionException extends DatabaseConnectionException
{
	public $hostname;
	public $username;
	public $database;

	public function __construct ($hostname, $username, $database)
	{
		parent::__construct ('could not connect to database');
		$this->hostname = $hostname;
		$this->username = $username;
		$this->database = $database;
	}
}

?>
