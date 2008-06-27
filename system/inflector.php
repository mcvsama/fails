<?php # vim: set fenc=utf8 ts=4 sw=4:

class Inflector
{
	private static $inflection_s2p_rules;
	private static $inflection_p2s_rules;
	private static $inflection_irregulars_s2p;
	private static $inflection_irregulars_p2s;
	private static $inflection_uncountables;

	/**
	 * Initializes inflector.
	 */
	public static function initialize()
	{
		Inflector::$inflection_s2p_rules = array();
		Inflector::$inflection_p2s_rules = array();
		Inflector::$inflection_irregulars_s2p = array();
		Inflector::$inflection_irregulars_p2s = array();
		Inflector::$inflection_uncountables = array();

		# Pluralization rules:
		Inflector::plural ('/$/', 's');
		Inflector::plural ('/s$/i', 's');
		Inflector::plural ('/(ax|test)is$/i', '\1es');
		Inflector::plural ('/(octop|vir)us$/i', '\1i');
		Inflector::plural ('/(alias|status)$/i', '\1es');
		Inflector::plural ('/(bu)s$/i', '\1ses');
		Inflector::plural ('/(buffal|tomat)o$/i', '\1oes');
		Inflector::plural ('/([ti])um$/i', '\1a');
		Inflector::plural ('/sis$/i', 'ses');
		Inflector::plural ('/(?:([^f])fe|([lr])f)$/i', '\1\2ves');
		Inflector::plural ('/(hive)$/i', '\1s');
		Inflector::plural ('/([^aeiouy]|qu)y$/i', '\1ies');
		Inflector::plural ('/([^aeiouy]|qu)ies$/i', '\1y');
		Inflector::plural ('/(x|ch|ss|sh)$/i', '\1es');
		Inflector::plural ('/(matr|vert|ind)ix|ex$/i', '\1ices');
		Inflector::plural ('/([m|l])ouse$/i', '\1ice');
		Inflector::plural ('/^(ox)$/i', '\1en');
		Inflector::plural ('/(quiz)$/i', '\1zes');

		# Singularization rules:
		Inflector::singular ('/s$/i', '');
		Inflector::singular ('/(n)ews$/i', '\1ews');
		Inflector::singular ('/([ti])a$/i', '\1um');
		Inflector::singular ('/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '\1\2sis');
		Inflector::singular ('/(^analy)ses$/i', '\1sis');
		Inflector::singular ('/([^f])ves$/i', '\1fe');
		Inflector::singular ('/(hive)s$/i', '\1');
		Inflector::singular ('/(tive)s$/i', '\1');
		Inflector::singular ('/([lr])ves$/i', '\1f');
		Inflector::singular ('/([^aeiouy]|qu)ies$/i', '\1y');
		Inflector::singular ('/(s)eries$/i', '\1eries');
		Inflector::singular ('/(m)ovies$/i', '\1ovie');
		Inflector::singular ('/(x|ch|ss|sh)es$/i', '\1');
		Inflector::singular ('/([m|l])ice$/i', '\1ouse');
		Inflector::singular ('/(bus)es$/i', '\1');
		Inflector::singular ('/(o)es$/i', '\1');
		Inflector::singular ('/(shoe)s$/i', '\1');
		Inflector::singular ('/(cris|ax|test)es$/i', '\1is');
		Inflector::singular ('/([octop|vir])i$/i', '\1us');
		Inflector::singular ('/(alias|status)es$/i', '\1');
		Inflector::singular ('/^(ox)en/i', '\1');
		Inflector::singular ('/(vert|ind)ices$/i', '\1ex');
		Inflector::singular ('/(matr)ices$/i', '\1ix');
		Inflector::singular ('/(quiz)zes$/i', '\1');

		# Irregulars:
		Inflector::irregular ('man', 'men');
		Inflector::irregular ('woman', 'women');
		Inflector::irregular ('child', 'children');
		Inflector::irregular ('person', 'people');
		Inflector::irregular ('sex', 'sexes');
		Inflector::irregular ('move', 'moves');
		Inflector::irregular ('octopus', 'octopi');

		# Uncountables:
		Inflector::uncountable ('equipment');
		Inflector::uncountable ('information');
		Inflector::uncountable ('rice');
		Inflector::uncountable ('money');
		Inflector::uncountable ('species');
		Inflector::uncountable ('series');
		Inflector::uncountable ('fish');
		Inflector::uncountable ('sheep');
	}

	/**
	 * Adds pluralization rule.
	 */
	public static function plural ($regexp, $target)
	{
		array_unshift (Inflector::$inflection_s2p_rules, array ($regexp, $target));
	}

	/**
	 * Adds singularization rule.
	 */
	public static function singular ($regexp, $target)
	{
		array_unshift (Inflector::$inflection_p2s_rules, array ($regexp, $target));
	}

	/**
	 * Adds irregular word pair.
	 */
	public static function irregular ($singular, $plural)
	{
		Inflector::$inflection_irregulars_s2p[$singular] = $plural;
		Inflector::$inflection_irregulars_p2s[$plural] = $singular;
	}

	/**
	 * Adds uncountable word.
	 */
	public static function uncountable ($uncountable)
	{
		Inflector::$inflection_uncountables[] = $uncountable;
	}

	/**
	 * Changes argument written in_underscore_notation
	 * to CamelCaseNotation.
	 *
	 * \param	string
	 * 			String to convert.
	 * \param	start_with_lower
	 * 			Whether first character in CamelCase should be lower case.
	 */
	public static function camelize ($string, $start_with_lower = false)
	{
		$string = str_replace (' ', '', ucwords (str_replace ('_', ' ', $string)));
		if ($start_with_lower)
			$string[0] = strtolower ($string[0]);
		return $string;
	}

	/**
	 * Changes argument written CamelCaseNotation
	 * to underscore_notation.
	 *
	 * \param	string
	 * 			String to convert.
	 */
	public static function underscore ($string)
	{
		return strtolower (preg_replace ('/(?<=\\w)([A-Z])/', '_\\1', $string));
	}

	/**
	 * Creates human-readable string from underscored-string.
	 *
	 * \param	string
	 * 			Underscored-string to convert.
	 */
	public static function humanize ($string)
	{
		return ucwords (str_replace ('_', ' ', $string));
	}

	/**
	 * Converts underscored controller name (as in routes)
	 * to controller class name.
	 */
	public static function to_controller_name ($string)
	{
		return Inflector::camelize ($string).'Controller';
	}

	/**
	 * Converts action name (as in routes)
	 * to method name.
	 */
	public static function to_action_name ($string)
	{
		return '_'.$string;
	}

	/**
	 * Converts to plural form.
	 */
	public static function pluralize ($subject)
	{
		# Check irregulars:
		if (isset (Inflector::$inflection_irregulars_s2p[$subject]))
			return Inflector::$inflection_irregulars_s2p[$subject];
		# Check uncountables:
		if (in_array ($subject, Inflector::$inflection_uncountables))
			return $subject;
		# Check rules:
		foreach (Inflector::$inflection_s2p_rules as $rule)
			if (preg_match ($rule[0], $subject))
				return preg_replace ($rule[0], $rule[1], $subject);
		throw new InflectorException ("can't pluralize word '{$subject}'");
	}

	/**
	 * Converts to singular form.
	 */
	public static function singularize ($subject)
	{
		# Check irregulars:
		if (isset (Inflector::$inflection_irregulars_p2s[$subject]))
			return Inflector::$inflection_irregulars_p2s[$subject];
		# Check uncountables:
		if (in_array ($subject, Inflector::$inflection_uncountables))
			return $subject;
		# Check rules:
		foreach (Inflector::$inflection_p2s_rules as $rule)
			if (preg_match ($rule[0], $subject))
				return preg_replace ($rule[0], $rule[1], $subject);
		throw new InflectorException ("can't singularize word '{$subject}'");
	}
}

Inflector::initialize();

?>
