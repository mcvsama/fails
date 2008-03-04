<?php # vim: set fenc=utf8 ts=4 sw=4:

class Controller
{
	public $controller;
	public $dispatcher;
	public $logger;
	public $request;
	public $response;
	public $router;
	public $session;
	public $view;

	protected $params;
	protected $set;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		assert (Fails::$request !== null);
		assert (Fails::$router !== null);
		assert (Fails::$dispatcher->current_route !== null);

		$this->params = array_merge (Fails::$dispatcher->current_route->get_params(), Fails::$request->g, Fails::$request->p);
		$this->set = new stdclass();

		$this->dispatcher	= Fails::$dispatcher;
		$this->logger		= Fails::$logger;
		$this->session		= Fails::$session;
		$this->request		= Fails::$request;
		$this->response		= Fails::$response;
		$this->router		= Fails::$router;
		$this->controller	= Fails::$controller;
		$this->view			= Fails::$view;
	}

	/**
	 * Returns map of variables meant to be passed to view.
	 */
	public function get_variables_for_view()
	{
		$ary = array();
		foreach (get_object_vars ($this->set) as $k => $v)
			$ary[$k] = $v;
		return $ary;
	}
}

?>
