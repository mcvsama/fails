<?php # vim: set fenc=utf8 ts=4 sw=4:

##
## General exceptions
##

class ParserException extends Exception
{
}


class MethodMissingException extends Exception
{
	public function __construct ($name, $object)
	{
		parent::__construct ("missing method '$name' on object '".get_class ($object)."'");
	}
}


class ArgumentException extends Exception
{
}


class UnimplementedException extends Exception
{
}


class InvalidOperationException extends Exception
{
}


class StatusException extends Exception
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

class LoggerException extends Exception
{
}

##
## Inflector exceptions
##

class InflectorException extends Exception
{
}

##
## Response exceptions
##

class ResponseException extends Exception
{
}

##
## Dispatcher exceptions
##

class SecurityException extends Exception
{
}


class RequireFileException extends Exception
{
}


class MissingControllerException extends Exception
{
}


class MissingActionException extends Exception
{
}

##
## Controller exceptions
##

class DoubleRenderException extends Exception
{
	public function __construct()
	{
		parent::__construct ("can only render or redirect once per action");
	}
}

##
## Router exceptions
##

class RouteGenerationException extends Exception
{
}


class DuplicateRouteException extends Exception
{
	public $name;

	public function __construct ($name)
	{
		parent::__construct ("route with name '$name' already exists");
		$this->name = $name;
	}
}


class RouteNotFoundException extends Exception
{
	public $url;

	public function __construct ($url)
	{
		parent::__construct ("couldn't find route matching URL '$url'");
		$this->url = $url;
	}
}


class RoutePathInvalidException extends Exception
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

class ViewMissingException extends Exception
{
}


class ViewParserException extends Exception
{
}


class ViewEngineAlreadyRegisteredException extends Exception
{
	public $processor;

	public function __construct (ViewerFactory $processor)
	{
		parent::__construct ("view engine '{$processor->identifier()}' already registered");
		$this->processor = $processor;
	}
}


class ViewEngineMissingException extends Exception
{
	public $identifier;

	public function __construct ($identifier)
	{
		parent::__construct ("view engine identifier by '{$identifier}' not registered");
		$this->identifier = $identifier;
	}
}


class ViewConfigurationException extends Exception
{
}

?>
