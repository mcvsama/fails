<?php

class TestController extends Controller
{
	function _test_routes()
	{
		# TODO na response
		#$this->response->set_header ('Content-Type', 'text/plain; charset=UTF-8');
		#$this->response->set_content_type ('text/plain; charset=UTF-8');
		# TYP MIME ustawiany na podstawie tego, co jest renderowane (typ widoku).
		# Lub jako parametr $this->render ('name', …, Response::TYPE_XHTML).
		# Dlatego trza uważać, ale można to wyłączyć: $this->auto_set_mime = false; czy coś podobnego
		$this->response->set_header ('Content-Type', 'text/html; charset=UTF-8');
		$this->view->render_action ('test.xhtml');
	}
}

?>
