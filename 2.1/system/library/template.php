<?php
class Template {
	private $data = array();
	private $template;

  public function __construct($driver) {
	    $class = 'Template\\' . $driver;

		if (class_exists($class)) {
			// Drivers take no constructor arguments; $expire was undefined (notice on every order view).
			$this->template = new $class();
		} else {
			exit('Error: Could not load template driver ' . $driver . ' cache!');
		}
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function render($template = '') {
		$file = DIR_TEMPLATE . $template;

		if (file_exists($file)) {
			extract($this->data);

			ob_start();

			require($file);

			$output = ob_get_contents();

			ob_end_clean();

			return $output;
		} else {
			trigger_error('Error: Could not load template ' . $file . '!');
			exit();
		}
	}
}
