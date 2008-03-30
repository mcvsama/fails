<?php # vim: set fenc=utf8 ts=4 sw=4:

class FailsViewerFactory extends ViewerFactory
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

Fails::$controller->register_viewer_factory (new FailsViewerFactory());

?>
