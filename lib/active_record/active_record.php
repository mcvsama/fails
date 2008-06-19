<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';

class ActiveRecord implements ArrayAccess
{
	# Internals object:
	protected $_;

	/**
	 * Creates new instance of singular (new) record.
	 *
	 * \param	attributes_map
	 * 			Map of values that will be stored in record through setters.
	 *
	 * \param	db
	 * 			Link to database. If null, and $this->database exists,
	 * 			$this->database will be used as a name for named connection.
	 *
	 * \throws	InvalidDatabaseConnectionNameException
	 * 			When $db is null and no connection named by $this->database
	 * 			has been registered (or $this->database is missing).
	 *
	 * \throws	RelationDoesNotExistException
	 * 			When there is no relation corresponding to object's class name in database.
	 *
	 * \throws	InvalidAttributeNameException
	 * 			When trying to set not existing (in relation) attribute.
	 *
	 * All attributes from $attributes_map will be initialized via setters/getters,
	 * not directly.
	 */
	public function __construct (array $attributes_map = null, Database $db = null)
	{
		$this->_ = new stdclass();
		$this->_->new_record = true;

		# Database connection:
		$this->_->db = $db;
		if ($this->_->db === null)
		{
			$k = '';
			if (isset ($this->_database))
				$k = $this->_database;
			if (!isset (Database::$connections[$k]))
				throw new InvalidDatabaseConnectionNameException ("named database connection '{$k}' does not exist");
			$this->_->db = Database::$connections[$k];
		}

		# Relation info:
		$this->_->relation_name = Inflector::underscore (get_class ($this));
		$db_dump = $this->_->db->dump_relations();
		if (!isset ($db_dump[$this->_->relation_name]))
			throw new RelationDoesNotExistException ("database says that relation '{$this->_->relation_name}' for model '".get_class ($this)."' does not exist");
		$this->_->relation_info = $db_dump[$this->_->relation_name];

		# Attributes:
		$this->_->attributes_info = $this->_->db->dump_attributes_of ($this->_->relation_name);
		$a = array();
		foreach ($this->_->attributes_info as $att_name => $att_info)
			$a[$att_name] = null;
		$this->_->attributes = $a;
		$this->_->original_attributes = $a;

		# Initialize attributes:
		if (is_array ($attributes_map))
			foreach ($attributes_map as $k => $v)
				$this->$k = $v;

		# Backup original attributes values:
		$this->_->original_attributes = $this->_->attributes;
	}

	/**
	 * Creates nonsingular (existing in database) object.
	 */
	public static function create_from_row (array $attributes_map = null, Database $db = null)
	{
		#$ar = new ...?
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
		$this->assert_attribute_exist ($name);
		if (method_exists ($this, 'set_'.$name))
			return $this->{'set_'.$name} ($value);
		return $this[$name] = $value;
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
		$this->assert_attribute_exist ($name);
		if (method_exists ($this, 'get_'.$name))
			return $this->{'get_'.$name}();
		return $this[$name];
	}

	/**
	 * Implements:
	 * 	$ar->attribute_changed()
	 * 	$ar->attribute_was()
	 */
	public function __call ($name, $arguments)
	{
		if (preg_match ('/^(.*)_changed$/', $name, $out))
		{
			$att_name = $out[1];
			$this->assert_attribute_exist ($att_name);
			return $this[$att_name] !== $this->_->original_attributes[$att_name];
		}
		else if (preg_match ('/^(.*)_was$/', $name, $out))
		{
			$att_name = $out[1];
			$this->assert_attribute_exist ($att_name);
			return $this->_->original_attributes[$att_name];
		}
		else
			throw new MethodMissingException ($name, $this);
	}

	##
	## Normal methods
	##

	/**
	 * Updates attributes, but only those which are present
	 * in attributes_map.
	 */
	public function update_attributes (array $attributes_map)
	{
		foreach ($attributes_map as $k => $v)
			$this->$k = $v;
	}

	public function new_record()
	{
		return $this->_->new_record;
	}

	public function changed()
	{
		foreach ($this->_->original_attributes as $a => $v)
			if ($this[$a] !== $v)
				return true;
		return false;
	}

	# Narpiew before-save, potem before-create/before-update
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

	##
	## ArrayAccess
	##

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 *
	 * Returns true if record has given attribute.
	 */
	public function offsetExists ($attribute)
	{
		return array_key_exists ($attribute, $this->_->original_attributes);
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 *
	 * Returns value of given attribute not filtered by getter.
	 */
	public function offsetGet ($attribute)
	{
		$this->assert_attribute_exist ($attribute);
		return $this->_->attributes[$attribute];
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 *
	 * Sets attribute without calling setter.
	 */
	public function offsetSet ($attribute, $value)
	{
		$this->assert_attribute_exist ($attribute);
		return $this->_->attributes[$attribute] = $value;
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset().
	 *
	 * Throws an exception. You can't unset ActiveRecord's attribute.
	 */
	public function offsetUnset ($offset)
	{
		throw new InvalidOperation ("can't unset ActiveRecord's attribute");
	}

	##
	## Privates
	##

	private function assert_attribute_exist ($name)
	{
		if (!array_key_exists ($name, $this->_->original_attributes))
			throw new InvalidAttributeNameException ($this, $name);
	}
}

?>
