<?php

class Nav_Tree extends Tree
{

	protected const CLASS_BY_CODE = [
		'a' => 'archive',
	];

	/**
	 * @var string[]
	 */
	public $classes = [];

	/**
	 * @var string
	 */
	public $link = '';

	/**
	 * @param $elements array|string[]
	 * @param $title    string
	 */
	public function __construct(?array $elements, string $title = null)
	{
		static $depth = -1;
		$depth ++;

		$depth
			? parent::__construct($elements, $title)
			: parent::__construct(reset($elements), key($elements));

		$depth --;
	}

	/**
	 * @param $class_codes string[]
	 */
	protected function constructClasses(array $class_codes)
	{
		foreach ($class_codes as $class_code) {
			$this->classes[] = static::CLASS_BY_CODE[$class_code];
		}
	}

	protected function constructStringElement(string $element)
	{
		if (preg_match_all('/\((\w+)\)/', $element, $matches)) {
			$this->constructClasses($matches[1]);
			$element = preg_replace('/\(\w*\)/', '', $element);
		}
		if ($position = strpos($element, '>')) {
			$this->link = trim(substr($element, $position + 1));
			$element    = trim(substr($element, 0, $position));
		}
		$this->title = $element;
	}


	public function strClasses() : string
	{
		return join(' ', $this->classes);
	}
}
