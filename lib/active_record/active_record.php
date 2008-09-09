<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';
require 'active_record_base.php';
require 'active_record_errors.php';

class ActiveRecord implements ArrayAccess
{
	const PRIMARY_KEY = 'id';

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
		$this->_ = new ActiveRecordBase();
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
		$this->_->relation_name = Inflector::pluralize (Inflector::underscore (get_class ($this)));
		$db_dump = $this->_->db->dump_relations();
		if (!isset ($db_dump[$this->_->relation_name]))
			throw new RelationDoesNotExistException ($this, "database says that relation '{$this->_->relation_name}' for model '".get_class ($this)."' does not exist");
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

		# Errors object:
		$this->_->errors = new ActiveRecordErrors ($this);
	}

	/**
	 * Creates nonsingular (existing in database) object.
	 */
	public static function create_from_row (array $attributes_map = null, Database $db = null)
	{
		$base = new ActiveRecordBase();
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

	/**
	 * Returns true if record is singular (does not
	 * exist in database).
	 */
	public function new_record()
	{
		return $this->_->new_record;
	}

	/**
	 * Returns true if any of record's attributes
	 * have been changed.
	 */
	public function changed()
	{
		if ($this->new_record())
			return true;
		foreach ($this->_->original_attributes as $a => $v)
			if ($this[$a] !== $v)
				return true;
		return false;
	}

	/**
	 * Used for data validation.
	 * To be overridden in child class.
	 */
	protected function validation (ActiveRecordErrors $errors)
	{ }

	/**
	 * Runs validation and returns true if object is valid.
	 */
	public function validate()
	{
		$this->_->errors = new ActiveRecordErrors ($this);
		$this->validation ($this->_->errors);
		if ($this->_->errors->has_errors())
			throw new ActiveRecordInvalidException ($this);
	}

	/**
	 * Runs validation and returns true if object is valid.
	 */
	public function valid()
	{
		try { $this->valid(); }
		catch (ActiveRecordInvalidException $exception)
		{ return false; }
		return true;
	}

	/**
	 * Returns errors object.
	 */
	public function errors()
	{
		return $this->_->errors;
	}

	/**
	 * \throws	ActiveRecordInvalidException
	 * 			when validation fails.
	 */
	public function save()
	{
		if ($this->new_record())
			return $this->insert();
		return $this->update();
	}

	/**
	 * \returns	false if validation fails, true otherwise.
	 */
	public function quiet_save()
	{
		try { $this->save(); }
		catch (ActiveRecordInvalidException $exception)
		{ return false; }
		return true;
	}

	/**
	 * Inserts record as new tuple into relation.
	 *
	 * \returns	$this for chaining.
	 *
	 * \throws	ActiveRecordInvalidException
	 * 			when validation fails.
	 */
	public function insert()
	{
		# Validation:
		$this->validate();
		# Create SQL:
		$relation = DatabaseQuery::e ($this->_->db->escape_relation_name ($this->_->relation_name));
		$n = 0;
		$integers = $attributes = $values = array();
		foreach ($this->_->attributes as $name => $value)
			if ($name !== ActiveRecord::PRIMARY_KEY) # Skip PRIMARY KEY
			{
				$integers[] = ++$n;
				$attributes[] = DatabaseQuery::e ($name);
				$values[] = $this[$name];
			}
		$sql = "INSERT INTO $relation (".join (', ', $attributes).") VALUES (:".join (', :', $integers).")";
		$this->_->db->exec (new DatabaseQueryWithArray ($sql, $values));
		# Get ID:
		$this[ActiveRecord::PRIMARY_KEY] = $this->_->db->sequence_value ($this->_->relation_name, ActiveRecord::PRIMARY_KEY);
		# Change state and backup attributes:
		$this->_->new_record = false;
		$this->_->original_attributes = $this->_->attributes;
		# Chaining:
		return $this;
	}

	/**
	 * Updates existing record in database.
	 *
	 * \returns	$this for chaining.
	 *
	 * \throws	ActiveRecordInvalidException
	 * 			when validation fails.
	 * \throws	ActiveRecordStateException
	 * 			when trying to update new record.
	 */
	public function update()
	{
		# Can't update new record:
		if ($this->new_record())
			throw new ActiveRecordStateException ($this);
		# Skip if record not changed:
		if (!$this->changed())
			return $this;
		# Validation:
		$this->validate();
		# Create SQL:
		$relation = DatabaseQuery::e ($this->_->db->escape_relation_name ($this->_->relation_name));
		$n = 0;
		$pairs = $attributes = $values = array();
		foreach ($this->_->attributes as $name => $value)
			if ($this[$name] !== $this->_->original_attributes[$name]) # Attribute changed?
			{
				$pairs[] = DatabaseQuery::e ($name).' = :'.++$n;
				$attributes[] = DatabaseQuery::e ($name);
				$values[] = $this[$name];
			}
		$values[] = $this->id;
		$this->_->db->exec (new DatabaseQueryWithArray ("UPDATE $relation SET ".join (', ', $pairs)." WHERE ".ActiveRecord::PRIMARY_KEY." = :".++$n, $values));
		# Backup attributes:
		$this->_->original_attributes = $this->_->attributes;
		# For chaining:
		return $this;
	}

	public function create (array $attributes_map = null)
	{
		# TODO
	}

	public function destroy()
	{
		# TODO
	}

	# create
	# destroy
	#
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
	# static destroy (by-id)
	# find (by-id)
	# find_or_create

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
		throw new InvalidOperationException ("can't unset ActiveRecord's attribute");
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

# TODO for testing, to remove:
class User extends ActiveRecord
{
	public function validation ($errors)
	{
		$errors->validate_as_nonblank ('username');
		$errors->validate_as_nonblank ('password');
	}
}

?>
