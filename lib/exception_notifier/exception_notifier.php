<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';

class ExceptionNotifier extends Library
{
	public static function notify (Exception $e)
	{
		$c = Fails::$config->exception_notifier;
		$title = ($c->tag? '['.$c->tag.'] ' : '').get_class ($e);
		$content = self::heading (get_class ($e))."\n".capitalize ($e->getMessage())."\n\n".$e->getTraceAsString()."\n\n";
		foreach (Fails::get_state() as $h => $c)
			$content .= self::heading ($h)."\n".$c."\n\n";
		foreach (self::mails() as $mail)
			@mail ($mail, $title, $content, 'Content-Type: text/plain; charset=UTF-8');
	}

	private static function mails()
	{
		$m = Fails::$config->exception_notifier->mails;
		if (is_string ($m))
			return array ($m);
		else
			return $m;
	}

	private static function heading ($string)
	{
		# Can't use str_pad, it does not support Unicode.
		$k = '';
		$n = strlen ($string);
		for ($i = 0; $i < $n; ++$i)
			$k .= '—';
		return $string."\n".$k."\n";
	}
}

#
# Check configuration:
#

if (!isset (Fails::$config->exception_notifier))
	throw new ExceptionNotifierConfigurationException ('missing Fails::$config->exception_notifier');

if (!is_array (Fails::$config->exception_notifier->mails) && !is_string (Fails::$config->exception_notifier->mails))
	throw new ExceptionNotifierConfigurationException ('Fails::$config->exception_notifier->mails is neither array nor string');

?>
