<?php # vim: set fenc=utf8 ts=4 sw=4:

class MCrypt extends Library
{
	private $iv;
	private $cipher;
	private $key;

	public function __construct ($initialization_vector, $key)
	{
		$this->iv = $initialization_vector;
		$this->key = $key;
		$this->cipher = mcrypt_module_open (MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
	}

	public function __destruct()
	{
		mcrypt_generic_deinit ($this->cipher);
	}

	public function encrypt ($text)
	{
		if (mcrypt_generic_init ($this->cipher, $this->key, $this->iv) != -1)
		{
			$t = mcrypt_generic ($this->cipher, $text);
			mcrypt_generic_deinit ($this->cipher);
			return $t;
		}
		else
			throw new Exception ('crypt error');
	}

	public function decrypt ($text)
	{
		if (mcrypt_generic_init ($this->cipher, $this->key, $this->iv) != -1)
		{
			$t = mdecrypt_generic ($this->cipher, $text);
			mcrypt_generic_deinit ($this->cipher);
			return $t;
		}
		else
			throw new Exception ('decrypt error');
	}

	/**
	 * Trims string at first found \0 byte.
	 */
	public function unpad_zero ($text)
	{
		# TODO
	}
}

?>
