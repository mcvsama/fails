<?php # vim: set fenc=utf8 ts=4 sw=4:

class ActiveRecordErrors
{
	public $record;
	public $list;

	public function __construct (ActiveRecord $record)
	{
		$this->record = $record;
		$this->list = array();
	}

	public function add ($field_name, $message)
	{
		$this->list[$field_name] = $message;
	}

	public function has_errors()
	{
		return count ($this->list) > 0;
	}

	public function validate_uniqueness_of ($field_name, $message = null)
	{
		# TODO zapytać bazę trzeba
	}

	public function validate_length_of ($field_name, $min_length, $max_length, $message = null)
	{
		$len = strlen ($this->get_data_for ($field_name));
		$min_length = intval ($min_length);
		$max_length = intval ($max_length);
		# Prepare message:
		if ($message === null)
		{
			$k = array();
			if ($min_length !== null)
				$k[] = "at least $min_length";
			if ($max_length !== null)
				$k[] = "at most $max_length";
			$message = 'must be '.join (' and ', $k).' characters long';
		}
		# Test:
		if (($min_length !== null && $len < $min_length) || ($max_length !== null && $max_length < $len))
			return $this->add ($field_name, $message);
		return true;
	}

	public function validate_format_of ($field_name, $regexp, $message = null)
	{
		if (!preg_match ($regexp, $this->get_data_for ($field_name)))
			return $this->add ($field_name, coalesce ($message, 'has invalid format'));
		return true;
	}

	public function validate_as_integer ($field_name, $message = null)
	{
		if (intval ($this->get_data_for ($field_name)) != $field_name)
			return $this->add ($field_name, coalesce ($message, 'must be integer'));
		return true;
	}

	public function validate_as_positive_integer ($field_name, $message = null)
	{
		if ($this->get_data_for ($field_name) <= 0)
			return $this->add ($field_name, coalesce ($message, 'must be positive'));
		return true;
	}

	public function validate_as_non_negative_integer ($field_name, $message = null)
	{
		if ($this->get_data_for ($field_name) < 0)
			return $this->add ($field_name, coalesce ($message, 'must be non-negative'));
		return true;
	}

	public function validate_as_negative_integer ($field_name, $message = null)
	{
		if ($this->get_data_for ($field_name) >= 0)
			return $this->add ($field_name, coalesce ($message, 'must be negative'));
		return true;
	}

	public function validate_as_non_positive_integer ($field_name, $message = null)
	{
		if ($this->get_data_for ($field_name) > 0)
			return $this->add ($field_name, coalesce ($message, 'must be non-positive'));
		return true;
	}

	public function validate_range_of ($field_name, $min_value, $max_value, $message = null)
	{
		$i = $this->get_data_for ($field_name);
		if ($i < $min || $max < $i)
			return $this->add ($field_name, coalesce ($message, "must be between $min_value and $max_value"));
		return true;
	}

	public function validate_as_nonempty ($field_name, $message = null)
	{
		if (strlen ($this->get_data_for ($field_name)) === 0)
			return $this->add ($field_name, coalesce ($message, 'can\'t be empty'));
		return true;
	}

	public function validate_as_nonblank ($field_name, $message = null)
	{
		if (trim ($this->get_data_for ($field_name)) === '')
			return $this->add ($field_name, coalesce ($message, 'can\'t be blank'));
		return true;
	}

	public function validate_value_of ($field_name, array $accepted_values, $message = null)
	{
		$data = $this->get_data_for ($field_name);
		foreach ($accepted_values as $e)
			if ($data == $e)
				return true;
		# No match, sorry:
		$this->add ($field_name, coalesce ($message, 'has invalid value'));
		return false;
	}

	public function validate_as_language_tag ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^[a-z]{2,}(-[a-z]+)*$/i", coalesce ($message, 'doesn\'t look like language tag'));
	}

	public function validate_as_alpha ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^([-a-z])+$/i", coalesce ($message, 'must only contain letters'));
	}

	public function validate_as_numeric ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^-?[0-9\.]+$/", coalesce ($message, 'must be numeric'));
	}

	public function validate_as_alphanumeric ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^([-a-z0-9])+$/i", coalesce ($message, 'must be alphanumeric'));
	}

	public function validate_as_alphanumeric_dash ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^([-a-z0-9_-])+$/i", coalesce ($message, 'must be alphanumeric'));
	}

	public function validate_as_ip ($field_name, $message = null)
	{
		return $this->validate_format_of ($field_name, "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", coalesce ($message, 'has invalid format'));
	}

	public function validate_as_iso_date ($field_name, $message = null)
	{
		return $this->_validate_as_iso_8601 ($field_name, coalesce ($message, 'is not valid date, it must have format YYYY-MM-DD'), 'date');
	}

	public function validate_as_iso_time ($field_name, $message = null)
	{
		return $this->_validate_as_iso_8601 ($field_name, coalesce ($message, 'is not valid time, it must have format HH:MM:SS'), 'time');
	}

	public function validate_as_iso_datetime ($field_name, $message = null)
	{
		return $this->_validate_as_iso_8601 ($field_name, coalesce ($message, 'is not valid date/time, it must have format YYYY-MM-DD HH:MM:SS'), 'datetime');
	}

	public function validate_as_iso_duration ($field_name, $message = null)
	{
		return $this->_validate_as_iso_8601 ($field_name, coalesce ($message, 'is not valid duration'), 'duration');
	}

	public function validate_as_emails ($field_name, $message)
	{
		foreach (explode (',', $this->get_data_for ($field_name)) as $e)
			if ($this->_validate_as_email (trim ($e), $field_name, $message, false) === false)
				return false;
		return true;
	}

	public function validate_as_local_emails ($field_name, $message)
	{
		foreach (explode (',', $this->get_data_for ($field_name)) as $e)
			if ($this->_validate_as_email (trim ($e), $field_name, $message, true) === false)
				return false;
		return true;
	}

	public function validate_as_email ($field_name, $message)
	{
		return $this->_validate_as_email ($this->get_data_for ($field_name), $field_name, $message, false);
	}

	public function validate_as_non_local_email ($field_name, $message)
	{
		return $this->_validate_as_email ($this->get_data_for ($field_name), $field_name, $message, true);
	}

	##
	## Privates
	##

	private function _validate_as_email ($data, $field_name, $message, $must_be_non_local)
	{
		# Format of E-mail (from RFC-822 <http://www.ietf.org/rfc/rfc822>)
		# (should write RFC-2822 compliant validation method in future):
		#
		# addr-spec			::= local-part "@" domain
		# local-part		::= word ("." word)*
		# domain			::= sub-domain ("." sub-domain)*
		# sub-domain		::= domain-ref | domain-literal
		# domain-literal	::= "[" (dtext|quoted-pair)* "]"
		# domain-ref		::= atom
		# word				::= atom | quoted-string
		# quoted-string		::= '"' (qtext|quoted-pair)* '"'
		#
		# atom				::= [any ASCII character excluding SPECIALS, SPACE and CTLs]+
		# 					  = [\x21\x23-\x27\x2a\x2b\x2d\x2f-\x39\x3d\x3f\x41-\x5a\x5e-\x7f]+
		# 					  = [-!#$%&'*+/0-9=?A-Z^_\x60a-z{|}~\x7f]+
		#
		# quoted-pair		::= "\" CHAR
		# 					  = \x5c[\x00-\x7f]
		#
		# qtext				::= any CHAR except '"' (0x22), "\" (0x5c) and CR (0x13) | lwsp
		# 					  = ([\x00-\x12\x14-\x21\x23-\x5b\x5d-\x7f]|[\x20\x09]+)
		#
		# dtext				::= any CHAR except "[" (0x5b), "]" (0x5d), "\" (0x5c), CR (0x13) | lwsp
		# 					  = ([\x00-\x12\x14-\x5a\x5e-\x7f]|[\x20\x09]+)
		#
		# lwsp				::= (SPACE|TAB)+
		# 					  = (\x20|\x09)+
		#
		# CTL				::= ASCII character in range [0x00..0x19]
		# CHAR				::= ASCII character in range [0x00..0x7f]
		# SPACE				::= ASCII character 0x20
		# TAB				::= ASCII character 0x09
		# SPECIALS			::= "(" (0x28) | ")" (0x29) | "<" (0x3c) | ">" (0x3e) | "@" (0x40) | "," (0x2c)
		# 					  | ";" (0x3b) | ":" (0x3a) | "\" (0x5c) | '"' (0x22) | "." (0x2e) | "[" (0x5b) | "]" (0x5d)

		# Terminal symbols:
		$quoted_pair		= '(\x5c[\x00-\x7f])';
		$lwsp				= '((\x20|\x09)+)';
		$qtext				= '([\x00-\x12\x14-\x21\x23-\x5b\x5d-\x7f]|'.$lwsp.')';
		$dtext				= '([\x00-\x12\x14-\x5a\x5e-\x7f]|'.$lwsp.')';
		$atom				= '([-!#$%&\'*+\/0-9=?A-Z^_\x60a-z{|}~\x7f]+)';

		# Non-terminal symbols:
		$quoted_string		= '(\x22('.$qtext.'|'.$quoted_pair.')*\x22)';
		$word				= '('.$atom.'|'.$quoted_string.')';
		$local_part			= '('.$word.'(\.'.$word.')*)';
		$domain_literal		= '(\x5b('.$dtext.'|'.$quoted_pair.')*\x5d)';
		$domain_ref			= $atom;
		$sub_domain			= '('.$domain_ref.'|'.$domain_literal.')';
		$domain				= '('.$sub_domain.'(\.'.$sub_domain.')*)';
		$non_local_domain	= '('.$sub_domain.'(\.'.$sub_domain.')+)';

		$email_regexp_1		= $local_part.'@'.$domain;
		$email_regexp_2		= $local_part.'@'.$non_local_domain;

		$regexp = $must_be_non_local? "/^$email_regexp_2$/" : "/^$email_regexp_1$/";

		if (!preg_match ($regexp, $data))
		{
			$this->add ($field_name, coalesce ($message, 'has invalid format'));
			return false;
		}
		return true;
	}

	/**
	 * \param	type
	 * 			One of 'date', 'time', 'datetime', 'duration'.
	 */
	private function _validate_as_iso_8601 ($field_name, $message, $type)
	{
		# Terminal symbols:
		$delimiter		= '[ T]';

		$normal_date	= '(\d{2}|\d{4})-\d{2}-\d{2}';
		$truncated_date	= '(\d{2}|\d{4})\d{2}\d{2}';

		$normal_time	= '(\d{2}:\d{2}(:\d{2})?)';
		$truncated_time	= '(\d{2}\d{2}(\d{2})?)';

		$time_decimal	= '(\.\d+)';
		$zone_offset	= '([-+]\d{2}(:\d{2})?)';

		# Non-terminal symbols:
		$date			= '('.$normal_date.'|'.$truncated_date.')';
		$time			= '('.$normal_time.'|'.$truncated_time.')'.$time_decimal.'?';

		switch ($type)
		{
			case 'date':		$regex = $date; break;
			case 'time':		$regex = $time.$zone_offset.'?'; break;
			case 'datetime':	$regex = $date.$delimiter.$time.$zone_offset.'?'; break;
			case 'duration';
				# TODO
				throw new Exception ("ISO duration: UNIMPLEMENTED");
				break;
		}

		return $this->validate_format_of ($field_name, "/^$regex$/", $message);
	}

	private function get_data_for ($field_name)
	{
		return $this->record[$field_name];
	}
}

?>
