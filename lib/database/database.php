<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';
require 'database_driver.php';
require 'database_query.php';
require 'database_result.php';

class Database
{
	# Map of named database connections:
	public static $connections = array();
}

?>
