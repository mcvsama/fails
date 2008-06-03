<?php # vim: set fenc=utf8 ts=4 sw=4:

class FailsViewerException extends Exception
{
}


class ViewParameterMissingException extends FailsViewerException
{
	public $param;

	public function __construct ($param)
	{
		parent::__construct ("missing view parameter: '$param'", null);
		$this->param = $param;
	}
}

?>
