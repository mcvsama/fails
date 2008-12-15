<?php # vim: set fenc=utf8 ts=4 sw=4:

class Response
{
	private $content_type;
	private $body;
	private $status_code;
	private $status_message;
	private $redirection;
	private $headers;

	/**
	 * Ctor
	 */
	public function __construct()
	{
		$this->set_content_type ('text/html; charset=UTF-8');
		$this->set_status (200, 'OK');
		$this->redirect_to (null);
		$this->headers = array();
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
	 * Returns Content-Type.
	 */
	public function get_content_type()
	{
		return $this->content_type;
	}

	/**
	 * Sets HTTP status for response.
	 */
	public function set_status ($code, $message = '')
	{
		if (!is_integer ($code))
			throw new ResponseException ("status code '{$code}' should be integer");
		$this->status_code = $code;
		$this->status_message = $message;
	}

	/**
	 * Gets HTTP status.
	 */
	public function get_status_code()
	{
		return $this->status_code;
	}

	/**
	 * Sets cookie.
	 */
	public function set_cookie ($name, $value, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
	{
		setcookie ($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * Sets response content.
	 */
	public function set_body ($body)
	{
		$this->body = $body;
	}

	/**
	 * Gets response content.
	 */
	public function get_body()
	{
		return $this->body;
	}

	/**
	 * \param	url
	 * 			URL to redirect to.
	 * 			If relative URL is given, it is relative to base-url of framework installation.
	 */
	public function redirect_to ($url)
	{
		if ($url === null)
			$this->redirection = null;
		else
			$this->redirection = $url;
	}

	/**
	 * Redirects to the same URL.
	 */
	public function reload()
	{
		$this->redirect_to (Fails::$request->url());
	}

	/**
	 * Redirects to Referer or throws exception when Referer
	 * is not set.
	 */
	public function redirect_back()
	{
		$referer = Fails::$request->referer();
		if ($referer === null)
			throw new RedirectException ("couldn't redirect to referer: referer not sent by browser");
		$this->redirect_to ($referer);
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
	public function fully_qualified_redirection_url()
	{
		$k = $this->redirection;
		# If there is ':' in URL it must be absolute URL:
		$a = strpos ($this->redirection, ':');
		if ($a !== false)
			return $this->redirection;
		# Otherwise, prepend base URL:
		return Fails::$request->fully_qualified_base_url().'/'.ltrim ($this->redirection, '/');
	}

	/**
	 * Returns response to User-Agent.
	 */
	public function answer()
	{
		# Etagging:
		if (Fails::$config->fails->auto_etagging === true && $this->status_code === 200 && $this->body !== '')
		{
			$this->headers['Etag'] = $this->etag_for ($this->body);
			if (@Fails::$request->env['HTTP_IF_NONE_MATCH'] === $this->headers['Etag'])
			{
				$this->set_status (304, 'Not Modified');
				$this->body = '';
			}
		}
		# Caching:
		if (Fails::$config->fails->prevent_caching === false)
		{
			$this->headers['Cache-Control'] = '';
			$this->headers['Pragma'] = '';
		}
		# Content-Type:
		header ('Content-Type: '.$this->content_type);
		# Headers:
		foreach ($this->headers as $header_name => $content)
			header ($header_name.': '.$content);
		# Redirection:
		if ($this->is_redirected())
		{
			$r = $this->fully_qualified_redirection_url();
			header ('Location: '.$r);
			Fails::$logger->add (Logger::CLASS_INFO, 'Redirection to '.$r);
		}
		else
		{
			$r = $this->status_code.' '.$this->status_message;
			header ('HTTP/1.1 '.$r);
			Fails::$logger->add (Logger::CLASS_INFO, 'Response <'.$this->content_type.'> '.$r);
		}
		# Enable output compression? Careful not to compress two times, when it's enabled in php.ini:
		$compression = Fails::$config->fails->output_compression && !ini_get('zlib.output_compression');
		if ($compression)
			ob_start ('ob_gzhandler');
		# Write output:
		echo $this->body;
		# Flush compressed buffer:
		if ($compression)
			ob_end_flush();
		Fails::$logger->add (Logger::CLASS_INFO, "Response sent\n\n");
	}

	/**
	 * Returns ETag for given content.
	 */
	private function etag_for ($subject)
	{
		return md5 ($subject);
	}
}

?>
