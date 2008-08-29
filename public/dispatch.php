<?php # vim: set fenc=utf8 ts=4 sw=4:

$microtime_start = microtime();

define ('FAILS_ROOT', realpath (dirname ($_SERVER['SCRIPT_FILENAME']).'/..'));

require_once FAILS_ROOT.'/system/dispatcher.php';

new Dispatcher();

?>
