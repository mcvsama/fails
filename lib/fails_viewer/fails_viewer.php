<?php # vim: set fenc=utf8 ts=4 sw=4:

class FailsViewerFactory extends ViewProcessorFactory
{
	public function identifier()
	{
		return 'fphp';
	}

	public function extension()
	{
		return 'fphp';
	}

	public function instantiate ($contents, array $variables)
	{
		return new FailsViewer ($contents, $variables);
	}
}


# TODO Niech wyjątki przyjmują obiekt View, który wywołał był błąd.
# TODO Podobnie zrób z innymi wyjątkami, np. dla Routera i innych.
class FailsViewer extends ViewProcessor
{
	# Template contents and variable bindings:
	protected $contents;
	protected $variables;

	# Contents after replacements:
	private $replaced_contents;

	# Result returned by eval():
	private $evaled_result;

	# Replacements applied to view code before eval():
	public static $replacements = array (
		# <&> and </&> javascript tags: TODO ?
		'/<&>/'		=> "<script type='text/javascript'>/*<![CDATA[*/ ",
		'/<\/&>/'	=> " /*]]>*/</script>",
		# <? and <?= compatibility tags:
		'/<\?=/'	=> "<?php echo ",
		'/<\?\s+/'	=> "<?php ",
		# <% escaped %>: TODO
		'/<%/'		=> "<?php echo h(",
		'/%>/'		=> ")?>",
		# <# translations #>: TODO
		'/<#/'		=> "<?php echo t('",
		'/#>/'		=> ")?>",
	);

	/**
	 * Ctor
	 *
	 * \param	contents
	 * 			Template contents.
	 * \param	variables
	 * 			Map of variables passed to template.
	 */
	public function __construct ($contents, array $variables)
	{
		$this->contents = $contents;
		$this->variables = $variables;
	}

	/**
	 * Returns processed view content. Ready to be rendered in output.
	 */
	public function process()
	{
		try {
			# Bind variables to local scope:
			foreach ($this->variables as $k => $v)
				${$k} = $v;

			# Start output buffering:
			ob_start();

			# Do replacements:
			$this->replaced_contents = $this->contents;
			foreach (self::$replacements as $this->from => $this->to)
				$this->replaced_contents = preg_replace ($this->from, $this->to, $this->replaced_contents);

			$this->evaled_result = eval ('?>'.$this->replaced_contents.'<?php ');

			if ($this->evaled_result === false)
				throw new ViewParsingException ('Error parsing view code');
			else
				echo $this->evaled_result;
		}
		catch (Exception $e)
		{
			# Ensure that capture has ended and rethrow:
			ob_end_clean();
			throw $e;
		}

		# End output buffering:
		return $this->result = ob_get_clean();
	}
}


Fails::$view->register_factory (new FailsViewerFactory());

?>
