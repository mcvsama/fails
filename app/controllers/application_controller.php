<?php # vim: set fenc=utf8 ts=4 sw=4:

class ApplicationController extends Controller
{
	# Database connection:
	protected $db;

	public function configure()
	{
		$this->db = new PostgreSQLDriver ('localhost', 'mcv', '', 'fails');
		Database::$connections[''] = $this->db;
	}

	public function before_filter()
	{
	}

	public function rescue_action (Exception $e)
	{
		ExceptionNotifier::notify ($e);
		throw $e;
	}

	public function after_filter()
	{
	}
}

?>
