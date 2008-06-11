<?php # vim: set fenc=utf8 ts=4 sw=4:

class Time
{
	private $stamp;

	public function __construct ($arg)
	{
		switch (true)
		{
			case is_string ($arg):
				$this->from_iso ($arg);
				break;

			case is_integer ($arg);
				$this->from_unix ($arg);
				break;
		}
	}

	public function from_unix ($unix_time)
	{
		$this->stamp = $unix_time;
	}

	public function from_iso ($iso_time)
	{
		$this->stamp = strtotime ($iso_time);
	}

	public function to_unix()
	{
		return $this->stamp;
	}

	public function to_iso()
	{
		return strftime ('%Y-%m-%d %R:%M', $this->stamp);
	}

	public function shift ($seconds, $minutes = 0, $hours = 0)
	{
		$this->stamp += $hours * 3600;
		$this->stamp += $minutes * 60;
		$this->stamp += $seconds;
	}

	public static function now()
	{
		return new Time (time());
	}
}

?>
