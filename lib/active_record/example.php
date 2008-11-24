<?php # vim: set fenc=utf8 ts=4 sw=4:

class Example extends ActiveRecord
{
	public $_serializes = array ('date', 'username', 'dupa');
	public $_foreign_keys = array ('owner', 'user', 'avatar');
	public $_database = '';
}

?>
