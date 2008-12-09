<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';
require 'active_record_base.php';
require 'active_record_errors.php';

# TODO przeanalizuj zwykłe metody: http://api.rubyonrails.org/classes/ActiveRecord/Base.html
# TODO przeanalizuj związki: http://api.rubyonrails.org/classes/ActiveRecord/Associations/ClassMethods.html
# TODO też poczytaj o „Eager loading”
# set_primary_key
# set_sequence_name
# set_table_name
# toggle(attribute)

class ActiveRecord implements ArrayAccess
{
	const PRIMARY_KEY = 'id';

	# Internals object:
	protected $_;

	protected static $db;
	protected static $relation_name;
	protected static $relation_info;
	protected static $attributes_info;
	protected static $attributes_prototype;

	/**
	 * Initializes record.
	 *
	 * \param	db
	 * 			Link to database. If null, and $this->database exists,
	 * 			$this->database will be used as a name for named connection.
	 *
	 * \throws	RelationDoesNotExistException
	 * 			When there is no relation corresponding to object's class name in database.
	 */
	public static function initialize ($class_name, Database $db = null)
	{
		self::$db = new ActiveRecordFinderBase();

		# Database connection:
		self::$db = $db;
		if (self::$db === null)
		{
			$k = '';
			if (isset (self::$database))
				$k = self::$database;
			if (!isset (Database::$connections[$k]))
				throw new InvalidDatabaseConnectionNameException ("named database connection '{$k}' does not exist");
			self::$db = Database::$connections[$k];
		}

		# Relation info:
		self::$relation_name = Inflector::pluralize (Inflector::underscore ($class_name));
		$db_dump = self::$db->dump_relations();
		if (!isset ($db_dump[self::$relation_name]))
			throw new RelationDoesNotExistException (null, "database says that relation '".self::$relation_name."' for model '".$class_name."' does not exist");
		self::$relation_info = $db_dump[self::$relation_name];

		# Attributes info and attributes array prototype:
		self::$attributes_info = self::$db->dump_attributes_of (self::$relation_name);
		self::$attributes_prototype = array();
		foreach (self::$attributes_info as $att_name => $att_info)
			self::$attributes_prototype[$att_name] = null;
	}

	/**
	 * Creates new instance of singular (new) record.
	 *
	 * \param	attributes_map
	 * 			Map of values that will be stored in record through setters.
	 *
	 * \throws	InvalidDatabaseConnectionNameException
	 * 			When $db is null and no named connection is defined for record.
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
		$this->_->is_new = true;

		$this->_->attributes = self::$attributes_prototype;
		$this->_->original_attributes = self::$attributes_prototype;

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
	 * Attributes map are setup without using setters.
	 */
	public static function create_from_row (array $attributes_map = null, Database $db = null)
	{
		# TODO $record = new ...
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
	public function is_new()
	{
		return $this->_->is_new;
	}

	/**
	 * Returns true if any of record's attributes
	 * have been changed.
	 */
	public function changed()
	{
		if ($this->is_new())
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
	 * \throws	ActiveRecordInvalidException
	 * 			on errors.
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
		if ($this->is_new())
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
		$relation = DatabaseQuery::e (self::$db->escape_relation_name (self::$relation_name));
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
		self::$db->exec (new DatabaseQueryWithArray ($sql, $values));
		# Get ID:
		$this[ActiveRecord::PRIMARY_KEY] = self::$db->sequence_value (self::$relation_name, ActiveRecord::PRIMARY_KEY);
		# Change state and backup attributes:
		$this->_->is_new = false;
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
		if ($this->is_new())
			throw new ActiveRecordStateException ($this);
		# Skip if record not changed:
		if (!$this->changed())
			return $this;
		# Validation:
		$this->validate();
		# Create SQL:
		$relation = DatabaseQuery::e (self::$db->escape_relation_name (self::$relation_name));
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
		self::$db->exec (new DatabaseQueryWithArray ("UPDATE $relation SET ".join (', ', $pairs)." WHERE ".ActiveRecord::PRIMARY_KEY." = :".++$n, $values));
		# Backup attributes:
		$this->_->original_attributes = $this->_->attributes;
		# For chaining:
		return $this;
	}

	/**
	 * Creates object from given attributes and saves
	 * it to database with save() method.
	 */
	public function create (array $attributes_map = null)
	{
		# TODO
	}

	/**
	 * Destroys object in database.
	 */
	public function destroy()
	{
		# TODO
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
	## Static functions
	##

	/**
	 * Returns DB connection used by this model.
	 */
	public static function db()
	{
		return self::$db;
	}

	/**
	 * Returns relation name used in database (not escaped for SQL).
	 */
	public static function relation_name()
	{
		return self::$relation_name;
	}

	/**
	 * Returns relation info dumped by database.
	 */
	public static function relation_info()
	{
		return self::$relation_info;
	}

	/**
	 * Returns attributes info dumped by database.
	 */
	public static function attributes_info()
	{
		return self::$attributes_info;
	}

	public function relation_name()

	##
	## Privates
	##

	private function assert_attribute_exist ($name)
	{
		if (!array_key_exists ($name, $this->_->original_attributes))
			throw new InvalidAttributeNameException ($this, $name);
	}

	##
	## Finder methods
	##

	/**
	 * Takes variable number of arguments either record IDs or one array containing record IDs.
	 * If array is given function will return array (even if array contains one element).
	 * If IDs are passed directly as arguments, array is returned only when there are 2 or more
	 * arguments.
	 *
	 * Examples:
	 *   find (3) -> Returns record with ID = 3
	 *   find (array (3)) -> Returns array containing one record with ID = 3
	 *   find (3, 4) or find (array (3, 4)) -> Returns array of records with IDs 3 and 4
	 */
	public static function find()
	{
		$ids = func_get_args();
		if (is_array ($ids[0]))
			$ids = $ids[0];
		if (is_array ($ids[0]) && count ($ids) > 0)
			throw new ArgumentException ("can't take mixed array/integers arguments");
		# SQL:
		$relation = DatabaseQuery::e (self::db()->escape_relation_name (self::relation_name()));
		$r = self::db()->exec (new DatabaseQuery ("SELECT * FROM $relation WHERE ".ActiveRecord::PRIMARY_KEY." IN (:1)", $ids));
	}

	/**
	 * \param	options
	 * 			Map of options:
	 * 				'select'		Selected columns, defaults to '<relation_name>.*'.
	 * 				'conditions'	Conditions passed after WHERE clause. Can be array containing
	 * 								string of conditions and list of parameters like
	 * 								array ('username = :1 AND password = :2', $username, $password).
	 * 				'limit'
	 * 				'offset'
	 * 				'order'
	 */
	public static function find_all (array $options = array())
	{
		# Example: User::find_all ('select' => 'username', 'conditions' => 'created_at < now()', 'order' => 'username DESC, created_at ASC');
		User::find_all_by_
		# TODO
	}

	public static function find_first (array $options = array())
	{
		# TODO
	}

	/**
	 * Dynamic calling is stupid in one way, that is it fails to search
	 * by attributes containing string '_any_' in their name because '_and_'
	 * is used as a attribute name separator (in function's name).
	 * You'll have to use generic ActiveRecordFinder::find_first or ::find_all method.
	 */
	public static function __call ($name, $arguments)
	{
		if (preg_match ('/^find_(first|all)_by_(.+)$/', $name, $matches))
		{
			$count = $matches[1];
			$atts = explode ('_and_', $matches[2]);
			Fails::$logger->debug ('finding by '.implode (' and ', $atts));
			# TODO
			if ($count == 'all')
				return array();
			return null;
		}
		else
			throw new MethodMissingException ($name, $this);
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
