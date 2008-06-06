<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';

class ActiveRecord
{
	# Record's attributes:
	public $attributes;

	# Internals object:
	protected $_;

	/**
	 * Creates new instance of record.
	 */
	public function __construct ($attributes_map, Database $db)
	{
		$this->_ = new stdclass();
		# TODO
		$this->_->db = $db;
		$this->_->original_attributes = $attributes_map;
	}

	##
	## Magic begins
	##

	/**
	 * Implements setting attribute value.
	 * Also setting 'attribute_id' from 'attribute', if 'attribute' is instance
	 * of ActiveRecord.
	 *
	 * \throws	InvalidAttributeNameException
	 * 			when trying to set attribute not present in schema.
	 */
	public function __set ($name, $value)
	{
		if (!isset ($this->_->original_attributes[$name])
			throw new InvalidAttributeNameException ($this, $name);
		# TODO
	}

	/**
	 * Implements reading attribute value.
	 * Also finding 'attribute' (ActiveRecord object) if there is corresponding
	 * 'attribute_id'.
	 *
	 * \throws	InvalidAttributeNameException
	 * 			when trying to read attribute not present in schema.
	 */
	public function __get ($name)
	{
		if (!isset ($this->_->original_attributes[$name])
			throw new InvalidAttributeNameException ($this, $name);
		# TODO
	}

	/**
	 * Implements:
	 * 	$ar->attribute_changed()
	 * 	$ar->attribute_was()
	 */
	public function __call ($name, $arguments)
	{
		# TODO
	}

	##
	## Normal methods
	##

	/**
	 * Updates attributes, but only those which are present
	 * in attributes_map.
	 */
	public function update (array $attributes_map)
	{
		foreach ($attributes_map as $k => $v)
			$this->attributes[$k] = $v;
	}

	public function new_record()
	{
	}

	public function changed()
	{
	}

	# before-save
	# after-save
	#
	# before-create
	# after-create
	#
	# before-update
	# after-update
	#
	# before-destroy
	# after-destroy
	#
	# before-validation
	# after-validation
	#
	# after-find
	#
	# save
	# insert
	# update
	# create
	# destroy
	#
	# validate
	#
	# static destroy (by-id)
	# find (by-id)
}

?>
