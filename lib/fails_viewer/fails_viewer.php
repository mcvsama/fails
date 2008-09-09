<?php # vim: set fenc=utf8 ts=4 sw=4:

require 'exception.php';
require 'fails_viewer_factory.php';

# TODO Niech wyjątki przyjmują obiekt View, który wywołał był błąd.
# TODO Podobnie zrób z innymi wyjątkami, np. dla Routera i innych.
class FailsViewer extends Viewer
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
		# <&> and </&> javascript tags and escaping CDATA section.
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
		'/#>/'		=> "')?>",
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
				throw new ViewParserException ('Error on parsing view code');
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

	##
	## These methods will be available within view as $this->method_name().
	##

	/**
	 * Asserts that given variables has been set for view.
	 * Argument list is variable length.
	 *
	 * \throws FailsViewParameterMissingException if assertion fails.
	 */
	protected function assert_params()
	{
		foreach (func_get_args() as $param)
			if (!isset ($this->variables->$param))
				throw new FailsViewParameterMissingException ($param);
	}
}

?>
