<?php # vim: set fenc=utf8 ts=4 sw=4:

class Response
{
	private $content_type;
	private $content;
	private $status;
	private $redirection;
	private $headers;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->set_content_type ('text/html; charset=UTF-8');
		$this->set_status ('200 OK');
		$this->redirect_to (null);
	}

	/**
	 * Response headers. Those will have priority over set content-type and status.
	 */
	public function set_header ($header_name, $content)
	{
		$this->headers[$header_name] = $content;
	}

	/**
	 * Sets HTTP Content-Type for response.
	 */
	public function set_content_type ($content_type)
	{
		$this->content_type = $content_type;
	}

	/**
	 * Sets HTTP status for response.
	 */
	public function set_status ($status_code, $message)
	{
		$this->status = $status_code.' '.$message;
	}

	/**
	 * Sets response content.
	 */
	public function set_content ($content)
	{
		$this->content = $content;
	}

	/**
	 * \param	route
	 * 			URL to redirect to.
	 * 			If relative URL is given, it is relative to base-url of framework installation.
	 */
	public function redirect_to ($route)
	{
		if ($route === null)
			$this->redirection = null;
		else
			$this->redirection = $route;
	}

	/**
	 * \returns	redirection URL or null if not redirected.
	 */
	public function is_redirected()
	{
		return $this->redirection;
	}

	/**
	 * \returns	fully-qualified redirection URL.
	 */
	public function fully_qalified_redirection_url()
	{
		$k = $this->redirection;
		if ($k[0] == '/')
	}

	/**
	 * Returns response to User-Agent.
	 */
	public function answer()
	{
		header ('Content-Type: '.$this->content_type);
		header ('Status: '.$this->status);
		# Headers:
		foreach ($this->headers as $header_name => $content)
			header ($header_name.': '.$content);
		# Redirection:
		if ($this->is_redirected())
			header ('Location: '.$this->fully_qalified_redirection_url());
		# TODO set ETag and check if we should respons Not-Modified or respond with full body.
		echo $this->content;
	}
}

?>
