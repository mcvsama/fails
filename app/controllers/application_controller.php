<?php # vim: set fenc=utf8 ts=4 sw=4:

class ApplicationController extends Controller
{
	# Database connection:
	protected $db;

	public function before_filter()
	{
		$this->db = new PostgreSQLDriver ('localhost', 'mcv', '', 'infopedia');
	}
}

?>
