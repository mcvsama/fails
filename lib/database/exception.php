<?php # vim: set fenc=utf8 ts=4 sw=4:

class DatabaseException extends FailsException
{
}


class DatabaseConnectionException extends DatabaseException
{
}


class MissingParametersForPlaceholdersException extends DatabaseException
{
}


class InvalidDatabaseConnectionNameException extends DatabaseException
{
}


class UnsupportedTypeForSQLException extends DatabaseException
{
	public $object;

	public function __construct ($object, $message = null, $code = null)
	{
		parent::__construct ($message, $code);
		$this->object = $object;
	}
}


class InvalidDatabaseQueryException extends DatabaseException
{
	public $query;

	public function __construct (DatabaseQuery $query, $message = null, $code = null)
	{
		parent::__construct ($message, $code);
		$this->query = $query;
	}
}


class DatabaseResultReadOnlyException extends DatabaseException
{
	public $result;

	public function __construct (DatabaseResult $result, $message = null, $code = null)
	{
		parent::__construct ($message, $code);
		$this->result = $result;
	}
}

?>
