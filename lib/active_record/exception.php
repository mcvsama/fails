<?php # vim: set fenc=utf8 ts=4 sw=4:

class ActiveRecordException extends Exception
{
	public $record;

	public function __construct (ActiveRecord $record, $message = null)
	{
		parent::__construct ($message);
		$this->record = $record;
	}
}


class RecordNotFoundException extends ActiveRecordException
{
}


class ActiveRecordStateException extends ActiveRecordException
{
}


class ActiveRecordInvalidException extends ActiveRecordException
{
	public function __construct (ActiveRecord $record, $message = null)
	{
		parent::__construct ($record, coalesce ($message, 'Validation failed'));
	}
}


class RelationDoesNotExistException extends ActiveRecordException
{
}


class InvalidAttributeNameException extends ActiveRecordException
{
	public $attribute_name;

	public function __construct (ActiveRecord $record, $attribute_name)
	{
		parent::__construct ($record, "Access to undefined attribute '$attribute_name' on record ".get_class ($record));
		$this->attribute_name = $attribute_name;
	}
}

?>
