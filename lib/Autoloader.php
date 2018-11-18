<?php

class Autoloader
{

	public function autoload($class_name)
	{
		if (file_exists(__DIR__ . '/' . $class_name . '.php')) {
			/** @noinspection PhpIncludeInspection dynamic */
			include __DIR__ . '/' . $class_name . '.php';
		}
	}

	public function register()
	{
		include __DIR__ . '/../vendor/autoload.php';
		spl_autoload_register([$this, 'autoload'], true, true);
	}

}
