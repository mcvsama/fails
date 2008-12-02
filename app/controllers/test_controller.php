<?php # vim: set fenc=utf8 ts=4 sw=4:

class TestController extends ApplicationController
{
	public $layout = 'user';

	function _test_routes()
	{
		$this->response->set_content_type ('text/html; charset=UTF-8');
#		$q = new DatabaseQuery ('SELECT * FROM users WHERE username != :0 AND id IN (:1)', 'user', array (1, 2, 5, 12));
		$this->set->connection = $this->db;
		$this->render_text ($this->params['a']['b']);
		try {
			$u = new User();
			$u->username = '';
			$u->password = 'dupa';
			$u->save();
		}
		catch (ActiveRecordInvalidException $e)
		{
			var_dump ($e->record->errors()->list);
		}
#		list ($a, $b) = $this->params ('a', 'b', 'z');
	}
}

?>
