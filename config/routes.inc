<?php # vim: set fenc=utf8 ts=4 sw=4:

# Index page (empty route):
Fails::$router->connect ('index', '', array ('controller' => 'test', 'action' => 'test_routes'));

Fails::$router->with (array ('controller' => 'users'));
Fails::$router->connect ('user',			'user/:user_id/:action',	array ('action' => 'show'));
Fails::$router->connect ('user_show',		'user/:user_id/show');
Fails::$router->end_with();

# Default route with lowest priority (at the end):
Fails::$router->connect (null, ':controller/:action/:id', array ('id' => null));

?>
