<?php # vim: set fenc=utf8 ts=4 sw=4:

class View
{
	private $factories;

	/**
	 * Ctor
	 */
	public function __construct()
	{
	}

	public function register_factory (ViewProcessorFactory $factory)
	{
		if (isset ($this->factories[$factory->extension()]))
			throw new ViewEngineAlreadyRegisteredException ($factory);
		$this->factories[$factory->extension()] = $factory;
	}

	public function render_action ($action, $layout = null, $status = null)
	{
		$this->render_template (Fails::$dispatcher->controller_name.'/'.$action, $layout, $status);
	}

	public function render_template ($template_name, $layout = null, $status = null)
	{
		# Load template by extension: TODO
		$type = ''; # '.html', '.xml', '.json', '.text', etc
		$factory = $this->get_factory();
		$file_name = FAILS_ROOT.'/app/views/'.$template_name.$type.'.'.$factory->extension();
		$this->render_file ($file_name, $layout, $status);
	}

	public function render_file ($file_name, $layout = false, $status = null)
	{
		# Load template file:
		$content = @file_get_contents ($file_name);
		if ($content === false)
			throw new MissingViewException ("View template missing: '".$file_name."'");
		$processor = $this->get_processor ($content, Fails::$controller->get_variables_for_view());
		$this->render_text ($processor->process(), $layout, $status);
	}

	public function render_partial ($partial_name, array $locals = array(), $layout = null, $status = null)
	{
		# TODO
	}

	public function render_text ($text, $layout = null, $status = null)
	{
		# TODO obsługa layout i statusów
		# TODO właściwie to przekazać dane należy do response, a nie żadne echo durnowate.
		echo $text;
	}

	public function render_json ($json, $status = null)
	{
		# TODO Use json::encode()/decode from infopedia/php-framework
	}

	/**
	 * Returns appropriate processor factory object to use.
	 */
	private function get_factory()
	{
		return $this->factories['fphp'];
	}

	/**
	 * Returns appropriate processor object to use.
	 */
	private function get_processor ($content, $variables)
	{
		# TODO jeśli jest tylko jeden silnik, wybierz go. W przeciwnym razie rządaj określenia przez
		# kontroler jakiego silnika używać.
		return $this->get_factory()->instantiate ($content, $variables);
	}
}


interface ViewProcessorFactory
{
	/**
	 * Returns recognized template extension.
	 */
	public function extension();

	/**
	 * Creates new view processor.
	 *
	 * \param	content
	 * 			Template content.
	 * \param	variables
	 * 			Map of variables passed to template.
	 */
	public function instantiate ($content, array $variables);
}


interface ViewProcessor
{
	/**
	 * Processes template and returns result as string.
	 */
	public function process();
}

?>
