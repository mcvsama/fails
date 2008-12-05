<?php # vim: set fenc=utf8 ts=4 sw=4:

##
## Root Fails exception
##

class FailsException extends Exception
{
	public function __construct ($message = null, $code = 0)
	{
		parent::__construct ($message, $code);
		if (Fails::$logger)
			Fails::$logger->exception ($this);
	}
}

##
## General exceptions
##

class ParserException extends FailsException
{
}


class MethodMissingException extends FailsException
{
	public function __construct ($name, $object)
	{
		parent::__construct ("missing method '$name' on object '".get_class ($object)."'");
	}
}


class ArgumentException extends FailsException
{
}


class UnimplementedException extends FailsException
{
}


class InvalidOperationException extends FailsException
{
}


class StatusException extends FailsException
{
	public $status_code;
	public $status_message;

	public function __construct ($status_code, $status_message = null)
	{
		parent::__construct ("status: $status_code");

		$this->status_code = $status_code;
		$this->status_message = $status_message;
	}
}

##
## Logger exceptions
##

class LoggerException extends FailsException
{
}

##
## Inflector exceptions
##

class InflectorException extends FailsException
{
}

##
## Response exceptions
##

class ResponseException extends FailsException
{
}

##
## Dispatcher exceptions
##

class SecurityException extends FailsException
{
}


class RequireFileException extends FailsException
{
}


class MissingControllerException extends FailsException
{
}


class MissingActionException extends FailsException
{
}

##
## Controller exceptions
##

class DoubleRenderException extends FailsException
{
	public function __construct()
	{
		parent::__construct ("can only render or redirect once per action");
	}
}

##
## Router exceptions
##

class RouteGenerationException extends FailsException
{
}


class DuplicateRouteException extends FailsException
{
	public $name;

	public function __construct ($name)
	{
		parent::__construct ("route with name '$name' already exists");
		$this->name = $name;
	}
}


class RouteNotFoundException extends FailsException
{
	public $url;

	public function __construct ($url)
	{
		parent::__construct ("couldn't find route matching URL '$url'");
		$this->url = $url;
	}
}


class RoutePathInvalidException extends FailsException
{
	public $route;

	public function __construct (Route $route, $reason)
	{
		parent::__construct ("invalid route path '".$route->path."': ".$reason);
		$this->route = $route;
	}
}

##
## View exceptions
##

class ViewMissingException extends FailsException
{
}


class ViewParserException extends FailsException
{
}


class ViewEngineAlreadyRegisteredException extends FailsException
{
	public $processor;

	public function __construct (ViewerFactory $processor)
	{
		parent::__construct ("view engine '{$processor->identifier()}' already registered");
		$this->processor = $processor;
	}
}


class ViewEngineMissingException extends FailsException
{
	public $identifier;

	public function __construct ($identifier)
	{
		parent::__construct ("view engine identifier by '{$identifier}' not registered");
		$this->identifier = $identifier;
	}
}


class ViewConfigurationException extends FailsException
{
}

?>
