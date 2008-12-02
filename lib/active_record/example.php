<?php # vim: set fenc=utf8 ts=4 sw=4:

Example::has_many ('owners');
Example::has_one ('user');
Example::belongs_to ('owner', array ('source' => 'user'));

class Example extends ActiveRecord
{
	public $_serializes = array ('date', 'username', 'dupa');
	public $_foreign_keys = array ('owner', 'user', 'avatar');
	public $_database = '';
	public $_has_one = array (
		'user',
		'owner' => array ('source' => 'user', 'foreign_key' => 'owner234_id'),
	);

	public $_belongs_to = array (
		# Polymorphic association named 'about':
		'about' => array ('polymorphic' => true),
	);

	public $_has_many = array (
		# Use polymorphic assocation named 'about':
		'comments'	=> array ('as' => 'about', 'dependent' => 'destroy', 'order' => 'created_at DESC'),
	);

	public $_has_many = array (
		'example_users',
		'subscribing_users' => array ('through' => 'topic_user_subscriptions'),
	);

	public $_has_many = array (
		'voters' => array ('source' => 'user')
	);
}

# Belongs-to
# ——————————
#
# value in singular form, or
# key in singular form and an array containing
#  • optional :source key: model name in singular form -> defines class_name and foreign_key,
#    • :class_name is obtained from model name, by camelizing it,
#    • :foreign_key is obtained from model name by appending '_id' suffix;
#  • optional :class_name key (overrides :source) -> Active Record class name
#  • optional :foreign_key key (overrides :source) -> foreign key name pointing to other table
# 
# Has-one, has-many
# —————————————————
# value in singular form, or
# key in singular form and an array containing
#  • optional :source key: model name in singular form -> defines class_name and foreign_key,
#    • :class_name is obtained from model name, by camelizing it,
#    • :foreign_key is obtained from model name by appending '_id' suffix;
#  • optional :class_name key (overrides :source) -> Active Record class name
#  • optional :foreign_key key (overrides :source) -> foreign key used in other table
#
# Difference between has-one and has-many is that has-one returns always one
# record though association and has-many returns array (array-proxy) of elements.
#
#
# Others:
# :select "SELECT #{relation_name}.* FROM"
# :include (eager loading)
# :readonly

?>
