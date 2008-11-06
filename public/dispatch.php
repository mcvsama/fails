<?php # vim: set fenc=utf8 ts=4 sw=4:

$runtime_control_points = array (microtime());

define ('FAILS_ROOT', realpath (dirname (__FILE__).'/..'));

require_once FAILS_ROOT.'/system/dispatcher.php';

new Dispatcher();

?>
