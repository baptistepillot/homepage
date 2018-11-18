<?php

/**
 * Tree
 */
class Tree
{

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var Tree[]
	 */
	public $elements = [];

	/**
	 * @param $elements array|string[]
	 * @param $title    string
	 */
	public function __construct(?array $elements, string $title = null)
	{
		if ($title) {
			$this->constructStringElement($title);
		}
		if ($elements) {
			$this->constructTreeElement($elements);
		}
	}

	protected function constructStringElement(string $title)
	{
		$this->title = $title;
	}

	protected function constructTreeElement(array $elements)
	{
		foreach ($elements as $title => $element) {
			$this->elements[] = is_array($element)
				? (new static($element, is_numeric($title) ? null : $title))
				: new static(null, $element);
		}
	}

}
