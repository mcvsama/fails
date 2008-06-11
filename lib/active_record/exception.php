<?php # vim: set fenc=utf8 ts=4 sw=4:

class ActiveRecordException extends Exception
{
}


class RelationDoesNotExistException extends ActiveRecordException
{
}


class InvalidAttributeNameException extends ActiveRecordException
{
	public $record;
	public $attribute_name;

	public function __construct (ActiveRecord $record, $attribute_name)
	{
		parent::__construct ("Access to undefined attribute '$attribute_name' on record ".get_class ($record));
		$this->record = $record;
		$this->attribute_name = $attribute_name;
	}
}

?>
