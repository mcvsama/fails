<?php # vim: set fenc=utf8 ts=4 sw=4:

class Time implements Marshal
{
	private $stamp;
	private $timezone;

	public function __construct ($arg = 0)
	{
		switch (true)
		{
			case is_string ($arg):
				$this->set_from_string ($arg);
				break;

			case is_integer ($arg);
				$this->set_from_unix ($arg);
				break;
		}
	}

	public function past()
	{
	}

	public function future()
	{
	}

	public function today()
	{
	}

	public function set_from_unix ($unix_time)
	{
		$this->stamp = $unix_time;
		return $this;
	}

	public function set_from_iso ($iso_time)
	{
		$this->stamp = strtotime ($iso_time);
		return $this;
	}

	public function set_from_string ($string)
	{
		$this->stamp = strtotime ($string);
		return $this;
	}

	public function to_unix()
	{
		return $this->stamp;
	}

	public function to_i()
	{
		return $this->stamp;
	}

	public function to_iso()
	{
		return strftime ('%Y-%m-%d %T', $this->stamp);
	}

	public function to_atom()
	{
		return strftime ('%Y-%m-%dT%TZ', $this->stamp);
	}

	public function to_http()
	{
		# TODO currently uses timezone set by setlocale()
		return date ('D, d M Y H:i:s T', $this->stamp);
	}

	public function shift ($seconds, $minutes = 0, $hours = 0)
	{
		$this->stamp += $hours * 3600;
		$this->stamp += $minutes * 60;
		$this->stamp += $seconds;
		return $this;
	}

	public function __toString()
	{
		return $this->to_iso();
	}

	public static function now()
	{
		return new Time (time());
	}

	public static function from_iso ($iso)
	{
		$t = new Time();
		$t->set_from_iso ($iso);
		return $t;
	}

	public static function from_unix ($unix)
	{
		$t = new Time();
		$t->set_from_unix ($unix);
		return $t;
	}

	public static function from_string ($string)
	{
		$t = new Time();
		$t->set_from_string ($string);
		return $t;
	}

	public static function dump ($object)
	{
		if (!($object instanceof Time))
			throw new ArgumentException ('Time::dump: expected object of type Time');
		return date ('c', $object->stamp);
	}

	public static function restore ($string)
	{
		return Time::from_iso ($string);
	}
}

?>
